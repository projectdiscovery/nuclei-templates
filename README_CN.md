

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
  <a href="https://nuclei.projectdiscovery.io/templating-guide/">文档</a> •
  <a href="#-贡献">贡献</a> •
  <a href="#-交流">交流</a> •
  <a href="#-社区">社区</a> •
  <a href="https://nuclei.projectdiscovery.io/faq/templates/">FAQs</a> •
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

## Nuclei模板TOP10统计信息

|    TAG    | COUNT |    AUTHOR     | COUNT | DIRECTORY  | COUNT | SEVERITY | COUNT | TYPE | COUNT |
|-----------|-------|---------------|-------|------------|-------|----------|-------|------|-------|
| cve       |  2877 | dhiyaneshdk   |  1477 | http       |  8219 | info     |  3948 | file |   404 |
| panel     |  1246 | daffainfo     |   866 | file       |   404 | high     |  2135 | dns  |    25 |
| wordpress |  1072 | dwisiswant0   |   803 | cloud      |   370 | medium   |  1840 |      |       |
| exposure  |  1006 | princechaddha |   570 | workflows  |   192 | critical |  1197 |      |       |
| xss       |   987 | ritikchaddha  |   496 | code       |   157 | low      |   287 |      |       |
| wp-plugin |   936 | pussycat0x    |   453 | network    |   138 | unknown  |    43 |      |       |
| osint     |   807 | pikpikcu      |   353 | javascript |    65 |          |       |      |       |
| tech      |   745 | pdteam        |   302 | ssl        |    30 |          |       |      |       |
| lfi       |   727 | ricardomaia   |   245 | dast       |    26 |          |       |      |       |
| misconfig |   720 | geeknik       |   231 | dns        |    22 |          |       |      |       |

**743 directories, 9960 files**.

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
