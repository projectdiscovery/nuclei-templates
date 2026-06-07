#!/usr/bin/env python3
"""
Enhanced KEV and vKEV Tagging Updater for Nuclei Templates

This script updates KEV and vKEV (VulnCheck Known Exploited Vulnerabilities) tags for all CVE templates by:
1. Fetching the latest CISA KEV catalog
2. Fetching VulnCheck's KEV data (if API key available)
3. Adding 'kev' tag for CISA KEV CVEs
4. Adding 'vkev' tag for VulnCheck KEV CVEs (including those not in CISA)
"""

import os
import re
import sys
import time
import json
import requests
from pathlib import Path
from typing import Set, Optional, Dict, Tuple

# Configuration
CISA_KEV_JSON_URL = "https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json"
VULNCHECK_KEV_API_URL = "https://api.vulncheck.com/v3/index/vulncheck-kev"
TIMEOUT = 30
MAX_RETRIES = 3
VULNCHECK_PAGE_SIZE = 100

class EnhancedKEVUpdater:
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.updated_count = 0
        self.error_count = 0
        self.cisa_kev_cves = set()
        self.vulncheck_kev_cves = set()
        self.vulncheck_api_key = os.getenv('VULNCHECK_API_KEY')

    def find_cve_templates(self) -> list[Path]:
        """Find all CVE template files."""
        cve_files = []

        # Search for CVE templates in common directories
        patterns = [
            "**/cves/**/*.yaml",
            "**/cve-*.yaml"
        ]

        for pattern in patterns:
            cve_files.extend(self.root_dir.glob(pattern))

        return sorted(list(set(cve_files)))

    def extract_cve_id(self, file_path: Path) -> Optional[str]:
        """Extract CVE ID from filename or template content."""
        # First try filename
        filename = file_path.stem
        cve_match = re.search(r'CVE-\d{4}-\d+', filename, re.IGNORECASE)
        if cve_match:
            return cve_match.group().upper()

        # If not found in filename, try content
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                cve_match = re.search(r'cve-id:\s*(CVE-\d{4}-\d+)', content, re.IGNORECASE)
                if cve_match:
                    return cve_match.group(1).upper()
        except Exception as e:
            print(f"Error reading {file_path}: {e}")

        return None

    def fetch_cisa_kev_data(self) -> Set[str]:
        """Fetch CISA KEV catalog and return set of CVE IDs."""
        kev_cves = set()

        for attempt in range(MAX_RETRIES):
            try:
                print("Fetching CISA KEV catalog...")
                response = requests.get(CISA_KEV_JSON_URL, timeout=TIMEOUT)
                response.raise_for_status()

                data = response.json()

                if 'vulnerabilities' in data:
                    for vuln in data['vulnerabilities']:
                        if 'cveID' in vuln:
                            kev_cves.add(vuln['cveID'].upper())

                    print(f"Retrieved {len(kev_cves)} CVEs from CISA KEV catalog")
                    break
                else:
                    print("Invalid CISA KEV data format")

            except requests.RequestException as e:
                print(f"CISA KEV fetch failed (attempt {attempt + 1}/{MAX_RETRIES}): {e}")
                if attempt == MAX_RETRIES - 1:
                    print("Failed to fetch CISA KEV data")
                else:
                    time.sleep(2 * (attempt + 1))

            except Exception as e:
                print(f"Unexpected error fetching CISA KEV: {e}")
                break

        return kev_cves

    def fetch_vulncheck_kev_data(self) -> Set[str]:
        """Fetch VulnCheck KEV catalog and return set of CVE IDs."""
        if not self.vulncheck_api_key:
            print("VulnCheck API key not available, skipping vKEV data fetch")
            return set()

        vkev_cves = set()
        page = 1
        total_pages = None
        pages_fetched = 0

        print("Fetching VulnCheck KEV catalog...")

        while True:
            for attempt in range(MAX_RETRIES):
                try:
                    headers = {
                        'Authorization': f'Bearer {self.vulncheck_api_key}',
                        'Accept': 'application/json'
                    }

                    params = {
                        'limit': VULNCHECK_PAGE_SIZE,
                        'page': page
                    }

                    response = requests.get(VULNCHECK_KEV_API_URL, headers=headers, params=params, timeout=TIMEOUT)
                    response.raise_for_status()

                    data = response.json()

                    if 'data' in data and data['data']:
                        for entry in data['data']:
                            if 'cve' in entry and entry['cve']:
                                for cve_id in entry['cve']:
                                    if cve_id and cve_id.startswith('CVE-'):
                                        vkev_cves.add(cve_id.upper())

                        # Check pagination info
                        meta = data.get('_meta', {})
                        total_pages = meta.get('total_pages', 0)
                        current_page = meta.get('page', page)

                        pages_fetched += 1
                        print(f"Fetched page {current_page}/{total_pages} ({len(data['data'])} entries, {len(vkev_cves)} unique CVEs so far)")

                        # Check if we've reached the last page
                        if current_page >= total_pages:
                            print(f"Completed fetching all {pages_fetched} pages")
                            return vkev_cves

                        # Rate limiting: small delay between requests to avoid hitting API limits
                        if pages_fetched % 10 == 0:  # Every 10 pages, longer delay
                            print(f"Pausing for rate limiting after {pages_fetched} pages...")
                            time.sleep(2)
                        else:
                            time.sleep(0.5)  # Small delay between requests

                        page += 1
                        break  # Break retry loop, continue to next page
                    else:
                        print("No more VulnCheck KEV data")
                        return vkev_cves

                except requests.RequestException as e:
                    print(f"VulnCheck KEV fetch failed (attempt {attempt + 1}/{MAX_RETRIES}, page {page}): {e}")
                    if attempt == MAX_RETRIES - 1:
                        print(f"Failed to fetch VulnCheck KEV data for page {page}")
                        return vkev_cves  # Return what we have so far
                    else:
                        # Exponential backoff with rate limiting consideration
                        delay = min(10, 2 * (attempt + 1))
                        print(f"Retrying after {delay} seconds...")
                        time.sleep(delay)

                except Exception as e:
                    print(f"Unexpected error fetching VulnCheck KEV: {e}")
                    return vkev_cves  # Return what we have so far

        print(f"Retrieved {len(vkev_cves)} CVEs from VulnCheck KEV catalog")
        return vkev_cves

    def has_tag(self, content: str, tag: str) -> bool:
        """Check if template already has specific tag in tags field."""
        # Look for tags field and check if tag is present
        tags_match = re.search(r'tags:\s*([^\n]+)', content)
        if tags_match:
            tags_str = tags_match.group(1)
            # Check for tag as a standalone tag (not part of another word)
            if re.search(rf'\b{tag}\b', tags_str, re.IGNORECASE):
                return True
        return False

    def add_tag(self, content: str, tag: str) -> tuple[str, bool]:
        """Add tag to the tags field. Returns (updated_content, was_updated)."""
        if self.has_tag(content, tag):
            return content, False

        # Find the tags field
        tags_match = re.search(r'(\s*tags:\s*)([^\n]+)', content)
        if tags_match:
            indent = tags_match.group(1)
            existing_tags = tags_match.group(2).rstrip()

            # Add tag at the end
            new_tags = existing_tags + f',{tag}'
            new_line = f"{indent}{new_tags}"

            updated_content = content.replace(tags_match.group(0), new_line)
            return updated_content, True
        else:
            print(f"Warning: No tags field found to add {tag} tag")
            return content, False

    def remove_tag(self, content: str, tag: str) -> tuple[str, bool]:
        """Remove tag from the tags field. Returns (updated_content, was_updated)."""
        if not self.has_tag(content, tag):
            return content, False

        # Find and update tags field
        tags_match = re.search(r'(\s*tags:\s*)([^\n]+)', content)
        if tags_match:
            indent = tags_match.group(1)
            existing_tags = tags_match.group(2)

            # Remove tag in various positions
            new_tags = existing_tags
            # Remove ,tag or tag, patterns
            new_tags = re.sub(rf',\s*{tag}\b', '', new_tags)
            new_tags = re.sub(rf'\b{tag}\s*,', '', new_tags)
            new_tags = re.sub(rf'^\s*{tag}\s*$', '', new_tags)  # tag is only tag
            new_tags = re.sub(rf'^\s*{tag}\s*,\s*', '', new_tags)  # tag is first tag

            if new_tags != existing_tags:
                new_line = f"{indent}{new_tags}"
                updated_content = content.replace(tags_match.group(0), new_line)
                return updated_content, True

        return content, False

    def update_template_tags(self, file_path: Path, cve_id: str, is_cisa_kev: bool, is_vulncheck_kev: bool) -> bool:
        """Update a template file with KEV and vKEV tags."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()

            original_content = content
            updated = False

            # Handle CISA KEV tag
            if is_cisa_kev:
                # Add kev tag if missing
                content, tag_added = self.add_tag(content, 'kev')
                if tag_added:
                    print(f"Added kev tag to {file_path.name}")
                    updated = True
            else:
                # Remove kev tag if present but CVE is not in CISA KEV catalog
                if self.has_tag(content, 'kev'):
                    # Only remove if we're confident about our KEV data
                    if len(self.cisa_kev_cves) > 1000:  # Sanity check
                        content, tag_removed = self.remove_tag(content, 'kev')
                        if tag_removed:
                            print(f"Removed kev tag from {file_path.name} (no longer in CISA KEV catalog)")
                            updated = True
                    else:
                        print(f"Warning: {cve_id} has kev tag but not in current CISA KEV catalog (keeping tag)")

            # Handle VulnCheck KEV tag
            if is_vulncheck_kev:
                # Add vkev tag if missing
                content, tag_added = self.add_tag(content, 'vkev')
                if tag_added:
                    print(f"Added vkev tag to {file_path.name}")
                    updated = True
            else:
                # Remove vkev tag if present but CVE is not in VulnCheck KEV catalog
                if self.has_tag(content, 'vkev') and len(self.vulncheck_kev_cves) > 100:  # Sanity check
                    content, tag_removed = self.remove_tag(content, 'vkev')
                    if tag_removed:
                        print(f"Removed vkev tag from {file_path.name} (no longer in VulnCheck KEV catalog)")
                        updated = True

            # Write updated content if changes were made
            if updated:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                return True

            return False

        except Exception as e:
            print(f"Error updating {file_path}: {e}")
            self.error_count += 1
            return False

    def run(self):
        """Main execution function."""
        print("Starting enhanced KEV/vKEV tagging update...")

        # Fetch KEV data
        self.cisa_kev_cves = self.fetch_cisa_kev_data()
        self.vulncheck_kev_cves = self.fetch_vulncheck_kev_data()

        if not self.cisa_kev_cves and not self.vulncheck_kev_cves:
            print("No KEV data available, exiting")
            return

        # Find all CVE templates
        template_files = self.find_cve_templates()
        print(f"Found {len(template_files)} CVE template files")

        if not template_files:
            print("No CVE templates found!")
            return

        # Process templates
        cisa_kev_found = 0
        vulncheck_kev_found = 0
        both_kev_found = 0

        for file_path in template_files:
            cve_id = self.extract_cve_id(file_path)
            if cve_id:
                is_cisa_kev = cve_id in self.cisa_kev_cves
                is_vulncheck_kev = cve_id in self.vulncheck_kev_cves

                if is_cisa_kev:
                    cisa_kev_found += 1
                if is_vulncheck_kev:
                    vulncheck_kev_found += 1
                if is_cisa_kev and is_vulncheck_kev:
                    both_kev_found += 1

                if self.update_template_tags(file_path, cve_id, is_cisa_kev, is_vulncheck_kev):
                    self.updated_count += 1
            else:
                print(f"Could not extract CVE ID from {file_path}")

        print(f"\nEnhanced KEV/vKEV update complete!")
        print(f"Templates updated: {self.updated_count}")
        print(f"CISA KEV CVEs found in templates: {cisa_kev_found}")
        print(f"VulnCheck KEV CVEs found in templates: {vulncheck_kev_found}")
        print(f"CVEs in both CISA and VulnCheck KEV: {both_kev_found}")
        print(f"CISA KEV catalog size: {len(self.cisa_kev_cves)}")
        print(f"VulnCheck KEV catalog size: {len(self.vulncheck_kev_cves)}")
        print(f"Errors: {self.error_count}")

def main():
    """Main entry point."""
    if len(sys.argv) > 1:
        root_dir = sys.argv[1]
    else:
        root_dir = os.getcwd()

    updater = EnhancedKEVUpdater(root_dir)
    updater.run()

if __name__ == "__main__":
    main()