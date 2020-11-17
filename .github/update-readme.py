#!/usr/bin/env python3
import argparse
import os
import subprocess

def countTpl(path):
    return len(os.listdir(path))

def command(args, start, end):
	return "\n".join(subprocess.run(args, shell=True, text=True, capture_output=True).stdout.split("\n")[start:end])[:-1]

if __name__ == "__main__":
	parser = argparse.ArgumentParser()
	parser.add_argument("--tag", help="Current repository tag", required=True)
	args = parser.parse_args()

	readme = f"""

# Nuclei Templates

[![License](https://img.shields.io/badge/license-MIT-_red.svg)](https://opensource.org/licenses/MIT)
[![GitHub Release](https://img.shields.io/github/release/projectdiscovery/nuclei-templates)](https://github.com/projectdiscovery/nuclei-templates/releases)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/projectdiscovery/nuclei-templates/issues)
[![Follow on Twitter](https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter)](https://twitter.com/pdnuclei)
[![Chat on Discord](https://img.shields.io/discord/695645237418131507.svg?logo=discord)](https://discord.gg/KECAGdH)

Templates are the core of [nuclei scanner](https://github.com/projectdiscovery/nuclei) which power the actual scanning engine. This repository stores and houses various templates for the scanner provided by our team as well as contributed by the community. We hope that you also contribute by sending templates via **pull requests** or [Github issue](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) and grow the list.

An overview of the nuclei template directory including number of templates and HTTP request associated with each directory. 

### Nuclei templates `{args.tag}`

| Template Directory | Number of Templates |
|----|----|
| cves | {countTpl("cves")} |
| default-credentials | {countTpl("default-credentials")} |
| dns | {countTpl("dns")} |
| files | {countTpl("files")} |
| generic-detections | {countTpl("generic-detections")} |
| panels | {countTpl("panels")} |
| security-misconfiguration | {countTpl("security-misconfiguration")} |
| subdomain-takeover | {countTpl("subdomain-takeover")} |
| technologies | {countTpl("technologies")} |
| tokens | {countTpl("tokens")} |
| vulnerabilities | {countTpl("vulnerabilities")} |
| workflows | {countTpl("workflows")} |

### Nuclei templates `{args.tag}` tree overview 

<details>
<summary> Nuclei templates </summary>

```
{command("tree", 1, -2)}
```
</details>

**{command("tree", -2, None)}**

Please navigate to https://nuclei.projectdiscovery.io for detailed documentation to build new and your own custom templates and many example templates for easy understanding. 

------
**Notes:** 
1. Use YAMLlint (e.g. [yamllint](http://www.yamllint.com/)) to validate new templates when sending pull requests.
2. Use YAML Formatter (e.g. [jsonformatter](https://jsonformatter.org/yaml-formatter)) to format new templates when sending pull requests.

Thanks again for your contribution and keeping the community vibrant. :heart:
"""

print(readme)
f = open("README.md", "w")
f.write(readme)
f.close()