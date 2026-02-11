#!/usr/bin/env python3

'''
This script fetches the top 1000 popular WordPress plugins using the WordPress.org Plugins API,
extracts each plugin's name, slug, and version, then generates a nuclei template and a payload
file with the latest version number to be used during scans that compare the detected version
with the payload version.

e.g.
nuclei -t http/technologies/wordpress/plugins -u https://www.example.com
'''

__author__ = "Kazgangap"

import html
import re
import os
import requests
from time import sleep


class WordPressPluginsUpdate:
    """Fetches popular WordPress plugins from the API and generates nuclei templates."""

    API_URL = "https://api.wordpress.org/plugins/info/1.2/"
    PER_PAGE = 100
    TOTAL_PLUGINS = 1000

    def __init__(self, work_dir=None):
        self.work_dir = work_dir or os.getcwd()
        self.helper_dir = os.path.join(self.work_dir, "helpers", "wordpress", "plugins")
        self.template_dir = os.path.join(self.work_dir, "http", "technologies", "wordpress", "plugins")

    @staticmethod
    def sanitize(text):
        """Strip HTML tags, decode HTML entities, and normalize whitespace."""
        text = re.sub(r"<[^>]+>", "", text)        # Remove HTML tags
        text = html.unescape(text)                 # Decode HTML entities (&#8211; -> –, &amp; -> &)
        text = re.sub(r'["`:]', "'", text)         # Replace quotes and colons with single quote
        text = re.sub(r"\s+", " ", text).strip()   # Collapse whitespace
        return text

    def fetch_plugins(self, page, max_retries=3):
        """Fetch a single page of popular plugins from the WordPress.org API.

        Retries up to max_retries times on failure with exponential backoff.
        """
        for attempt in range(1, max_retries + 1):
            try:
                response = requests.get(self.API_URL, params={
                    "action": "query_plugins",
                    "request[browse]": "popular",
                    "request[page]": page,
                    "request[per_page]": self.PER_PAGE,
                }, timeout=30)
                response.raise_for_status()
                return response.json().get("plugins", [])
            except requests.RequestException as e:
                if attempt < max_retries:
                    wait = 2 ** attempt
                    print(f"  Attempt {attempt}/{max_retries} failed for page {page}: {e}")
                    print(f"  Retrying in {wait}s...")
                    sleep(wait)
                else:
                    print(f"  All {max_retries} attempts failed for page {page}: {e}")
                    raise

    def build_template(self, slug, name, description):
        """Generate a nuclei YAML template string for a given plugin."""
        return f'''id: wordpress-{slug}

info:
  name: {name} - WordPress Plugin Detection
  author: Kazgangap
  severity: info
  description: |
    {description}
  reference:
    - https://wordpress.org/plugins/{slug}/
    - https://www.wordfence.com/threat-intel/vulnerabilities/wordpress-plugins/{slug}/
    - https://wpscan.com/plugin/{slug}/
  metadata:
    framework: wordpress
    fofa-query: body="/wp-content/plugins/{slug}/"
    shodan-query: http.html:"/wp-content/plugins/{slug}/"
    publicwww-query: "/wp-content/plugins/{slug}/"
  tags: tech,wordpress,wp-plugin,discovery

http:
  - method: GET
    path:
      - "{{{{BaseURL}}}}/wp-content/plugins/{slug}/readme.txt"

    payloads:
      last_version: helpers/wordpress/plugins/{slug}.txt

    extractors:
      - type: regex
        part: body
        internal: true
        name: internal_detected_version
        group: 1
        regex:
          - '(?i)Stable.tag:\s?([\w.]+)'

      - type: regex
        part: body
        name: detected_version
        group: 1
        regex:
          - '(?i)Stable.tag:\s?([\w.]+)'

    matchers-condition: or
    matchers:
      - type: dsl
        name: "outdated_version"
        dsl:
          - compare_versions(internal_detected_version, concat("< ", last_version))

      - type: regex
        part: body
        regex:
          - '(?i)Stable.tag:\s?([\w.]+)'
'''

    def run(self):
        """Main entry point: fetch plugins and write templates."""
        os.makedirs(self.helper_dir, exist_ok=True)
        os.makedirs(self.template_dir, exist_ok=True)

        total_pages = self.TOTAL_PLUGINS // self.PER_PAGE
        ranking = 1

        for page in range(1, total_pages + 1):
            print(f"\n{'='*60}")
            print(f"  Fetching API page {page}/{total_pages}...")
            print(f"{'='*60}\n")

            plugins = self.fetch_plugins(page)

            for plugin in plugins:
                slug = plugin["slug"]
                version = plugin.get("version", "N/A")
                name = self.sanitize(plugin.get("name", slug))
                description = self.sanitize(plugin.get("short_description", "N/A"))

                print(f"[{ranking}] {name} ({slug}) - v{version}")

                template = self.build_template(slug, name, description)

                helper_path = os.path.join(self.helper_dir, f"{slug}.txt")
                with open(helper_path, "w") as f:
                    f.write(version)

                template_path = os.path.join(self.template_dir, f"{slug}.yaml")
                with open(template_path, "w") as f:
                    f.write(template)

                ranking += 1

            sleep(1)  # Be polite to the API between pages

        print(f"\nDone. Generated {ranking - 1} plugin templates.")


if __name__ == "__main__":
    updater = WordPressPluginsUpdate()
    updater.run()
