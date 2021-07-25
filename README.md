

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
  <a href="https://nuclei.projectdiscovery.io/templating-guide/">Documentation</a> •
  <a href="#-contributions">Contributions</a> •
  <a href="#-discussion">Discussion</a> •
  <a href="#-community">Community</a> •
  <a href="https://nuclei.projectdiscovery.io/faq/templates/">FAQs</a> •
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

## Nuclei Templates Top 10 statistics

|    TAG    | COUNT |    AUTHOR     | COUNT |    DIRECTORY     | COUNT | SEVERITY | COUNT |  TYPE   | COUNT |
|-----------|-------|---------------|-------|------------------|-------|----------|-------|---------|-------|
| cve       |   467 | dhiyaneshdk   |   202 | cves             |   473 | info     |   453 | http    |  1409 |
| panel     |   201 | pdteam        |   184 | vulnerabilities  |   230 | high     |   395 | file    |    42 |
| xss       |   169 | pikpikcu      |   177 | exposed-panels   |   202 | medium   |   313 | network |    32 |
| wordpress |   164 | dwisiswant0   |   113 | exposures        |   150 | critical |   187 | dns     |    10 |
| rce       |   162 | geeknik       |    89 | technologies     |   129 | low      |   150 |         |       |
| exposure  |   154 | daffainfo     |    79 | misconfiguration |   114 |          |       |         |       |
| cve2020   |   137 | madrobot      |    59 | takeovers        |    70 |          |       |         |       |
| lfi       |   127 | princechaddha |    50 | default-logins   |    44 |          |       |         |       |
| wp-plugin |   110 | gaurang       |    42 | file             |    42 |          |       |         |       |
| cve2019   |    82 | gy741         |    36 | workflows        |    33 |          |       |         |       |

**134 directories, 1612 files**.

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

💬 Discussion
-----

Have questions / doubts / ideas to discuss?
Feel free to open a discussion on [Github discussions](https://github.com/projectdiscovery/nuclei-templates/discussions) board.

👨‍💻 Community
-----

You are welcome to join our [Discord Community](https://discord.gg/KECAGdH).
You can also follow us on [Twitter](https://twitter.com/pdiscoveryio) to keep up with everything related to projectdiscovery.

💡 Notes
-----
-  Use YAMLlint (e.g. [yamllint](http://www.yamllint.com/) to validate the syntax of templates before sending pull requests.


Thanks again for your contribution and keeping this community vibrant. :heart:
