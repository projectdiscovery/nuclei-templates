

<h1 align="center">
Nuclei Templates
</h1>
<h4 align="center">Community curated list of templates for the nuclei engine to find security vulnerabilities in applications.</h4>


<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/issues"><img src="https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat"></a>
<a href="https://github.com/projectdiscovery/nuclei-templates/releases"><img src="https://img.shields.io/github/release/projectdiscovery/nuclei-templates"></a>
<a href="https://twitter.com/pdnuclei"><img src="https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter"></a>
<a href="https://discord.gg/projectdiscovery"><img src="https://img.shields.io/discord/695645237418131507.svg?logo=discord"></a>
</p>
      
<p align="center">
  <a href="https://docs.projectdiscovery.io/templates/introduction">Documentation</a> •
  <a href="#-contributions">Contributions</a> •
  <a href="#-discussion">Discussion</a> •
  <a href="#-community">Community</a> •
  <a href="https://docs.projectdiscovery.io/templates/faq">FAQs</a> •
  <a href="https://discord.gg/projectdiscovery">Join Discord</a>
</p>

----

Templates are the core of the [nuclei scanner](https://github.com/projectdiscovery/nuclei) which powers the actual scanning engine.
This repository stores and houses various templates for the scanner provided by our team, as well as contributed by the community.
We hope that you also contribute by sending templates via **pull requests** or [Github issues](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) to grow the list.


## Nuclei Templates overview


An overview of the nuclei template project, including statistics on unique tags, author, directory, severity, and type of templates. The table below contains the top ten statistics for each matrix; an expanded version of this is [available here](TEMPLATES-STATS.md), and also available in [JSON](TEMPLATES-STATS.json) format for integration.

<table>
<tr>
<td>

### 🚨 Known Exploited Vulnerabilities (KEV) Coverage

Nuclei templates provide coverage for vulnerabilities actively exploited in the wild:

| **KEV Source** | **Templates** | **Description** |
|----------------|---------------|-----------------|
| 🔴 **CISA KEV** | **454** | [CISA Known Exploited Vulnerabilities Catalog](https://www.cisa.gov/known-exploited-vulnerabilities-catalog) |
| 🟠 **VulnCheck KEV** | **1449** | [VulnCheck KEV](https://vulncheck.com/kev) - Enhanced vulnerability intelligence |
| 🟢 **Both Sources** | **407** | Templates covering vulnerabilities in both catalogs |

> 💡 **Total unique KEV templates: 1496** - Use `nuclei -tags kev,vkev` to scan for actively exploited vulnerabilities

---

## Nuclei Templates Top 10 statistics

|    TAG    | COUNT |    AUTHOR     | COUNT | DIRECTORY  | COUNT | SEVERITY | COUNT | TYPE | COUNT |
|-----------|-------|---------------|-------|------------|-------|----------|-------|------|-------|
| vuln      |  6468 | dhiyaneshdk   |  1894 | http       |  9281 | info     |  4353 | file |   436 |
| cve       |  3587 | daffainfo     |   905 | cloud      |   659 | high     |  2552 | dns  |    26 |
| discovery |  3265 | princechaddha |   854 | file       |   436 | medium   |  2457 |      |       |
| vkev      |  1394 | dwisiswant0   |   805 | network    |   259 | critical |  1555 |      |       |
| panel     |  1365 | ritikchaddha  |   678 | code       |   251 | low      |   330 |      |       |
| xss       |  1269 | pussycat0x    |   675 | dast       |   240 | unknown  |    54 |      |       |
| wordpress |  1261 | pikpikcu      |   353 | workflows  |   205 |          |       |      |       |
| exposure  |  1141 | pdteam        |   314 | javascript |    92 |          |       |      |       |
| wp-plugin |  1103 | pdresearch    |   275 | ssl        |    38 |          |       |      |       |
| osint     |   848 | iamnoooob     |   263 | dns        |    23 |          |       |      |       |

**873 directories, 11997 files**.

</td>
</tr>
</table>

📖 Documentation
-----

Please navigate to https://nuclei.projectdiscovery.io for detailed documentation to **build** new or your own **custom** templates.
We have also added a set of templates to help you understand how things work.

💪 Contributions
-----

Nuclei-templates is powered by major contributions from the community.
[Template contributions ](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+), [Feature Requests](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=feature_request.md&title=%5BFeature%5D+) and [Bug Reports](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=bug_report.md&title=%5BBug%5D+) are more than welcome.

![Alt](https://repobeats.axiom.co/api/embed/55ee65543bb9a0f9c797626c4e66d472a517d17c.svg "Repobeats analytics image")

💬 Discussion
-----

Have questions / doubts / ideas to discuss?
Feel free to open a discussion on [Github discussions](https://github.com/projectdiscovery/nuclei-templates/discussions) board.

👨‍💻 Community
-----

You are welcome to join the active [Discord Community](https://discord.gg/projectdiscovery) to discuss directly with project maintainers and share things with others around security and automation.
Additionally, you may follow us on [Twitter](https://twitter.com/pdnuclei) to be updated on all the things about Nuclei.


<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=projectdiscovery/nuclei-templates&max=300">
</a>
</p>


Thanks again for your contribution and keeping this community vibrant. :heart:
