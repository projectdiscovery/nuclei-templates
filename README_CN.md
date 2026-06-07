

<h1 align="center">
Nuclei 模板
</h1>
<div align="center">
<a href="README.md">English</a> |
<a href="README_CN.md">简体中文</a> |
<a href="README_JA.md">日本語</a> |
<a href="README_KR.md">한국어</a>
</div>
<h4 align="center">这个仓库用于存放由社区精心挑选的模板,可以使用nuclei引擎结合模板发现应用中的漏洞。</h4>


<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/issues"><img src="https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat"></a>
<a href="https://github.com/projectdiscovery/nuclei-templates/releases"><img src="https://img.shields.io/github/release/projectdiscovery/nuclei-templates"></a>
<a href="https://twitter.com/pdnuclei"><img src="https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter"></a>
<a href="https://discord.gg/projectdiscovery"><img src="https://img.shields.io/discord/695645237418131507.svg?logo=discord"></a>
</p>

<p align="center">
  <a href="https://docs.projectdiscovery.io/templates/introduction">文档</a> •
  <a href="#-贡献">贡献</a> •
  <a href="#-交流">交流</a> •
  <a href="#-社区">社区</a> •
  <a href="https://docs.projectdiscovery.io/templates/faq">FAQs</a> •
  <a href="https://discord.gg/projectdiscovery">加入Discord</a>
</p>

----

模板是 [nuclei 扫描器](https://github.com/projectdiscovery/nuclei) 的核心.
这个git仓库储存了由PD团队以及社区贡献的各种扫描模板.
我们希望您也能攻通过 **pull requests** 或者[Github issues](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+)来提交模板以扩大我们的仓库列表.


## Nuclei模板项目情况概述


以下是nuclei模板项目的情况概览,包括唯一标签,作者,目录,严重性,模板类型的相关统计情况.以下表格列出了前面提到的每项情况的TOP10信息;你也可以点击[这里](TEMPLATES-STATS.md)查看详细信息.当然你也可以点击[这里](TEMPLATES-STATS.json)获取JSON格式的详细情况统计

<table>
<tr>
<td>

### 🚨 已知被利用漏洞 (KEV) 覆盖范围

Nuclei 模板为野外被积极利用的漏洞提供覆盖:

| **KEV 来源** | **模板数量** | **描述** |
|----------------|---------------|-----------------|
| 🔴 **CISA KEV** | **454** | [CISA 已知被利用漏洞目录](https://www.cisa.gov/known-exploited-vulnerabilities-catalog) |
| 🟠 **VulnCheck KEV** | **1449** | [VulnCheck KEV](https://vulncheck.com/kev) - 增强的漏洞情报 |
| 🟢 **两个来源** | **407** | 覆盖两个目录中漏洞的模板 |

> 💡 **唯一 KEV 模板总数: 1496** - 使用 `nuclei -tags kev,vkev` 扫描被积极利用的漏洞

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

📖 文档
-----

详细的文档请访问我们的网站[https://nuclei.projectdiscovery.io](https://nuclei.projectdiscovery.io),在我们网站的详细文档中,我们提供了如何创建模板的具体方法,并且也提供了相应的示例模板来帮助您更好地理解模板的开发以及运行原理.

💪 贡献
-----

社区是Nuclei模板项目的主要贡献主力,我们非常欢迎开发者们来贡献模板,提出需求和报告Bug.

![Alt](https://repobeats.axiom.co/api/embed/55ee65543bb9a0f9c797626c4e66d472a517d17c.svg "Repobeats analytics image")

💬 交流
-----

如果您有任何关于该项目的疑问或是新奇的点子,欢迎在[Github discussions](https://github.com/projectdiscovery/nuclei-templates/discussions)创建新的板块来进行讨论.

👨‍💻 社区
-----

欢迎您加入我们的[Discord 社区](https://discord.gg/projectdiscovery)，与项目维护人员直接讨论，或与其他人分享有关安全和自动化的想法。
此外，您还可以在 [Twitter](https://twitter.com/pdnuclei) 上关注我们，了解 Nuclei 的最新动态。


<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=projectdiscovery/nuclei-templates&max=300">
</a>
</p>


最后,感谢您对这个项目的贡献,这将让我们的社区更加充满活力.
