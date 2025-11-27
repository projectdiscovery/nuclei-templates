<h1 align="center">
Nuclei テンプレート
</h1>
<h4 align="center">アプリケーションのセキュリティ脆弱性を発見するためのNucleiエンジン用テンプレートのコミュニティキュレーションリスト</h4>

<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/issues"><img src="https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat"></a>
<a href="https://github.com/projectdiscovery/nuclei-templates/releases"><img src="https://img.shields.io/github/release/projectdiscovery/nuclei-templates"></a>
<a href="https://twitter.com/pdnuclei"><img src="https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter"></a>
<a href="https://discord.gg/projectdiscovery"><img src="https://img.shields.io/discord/695645237418131507.svg?logo=discord"></a>
</p>
      
<p align="center">
  <a href="https://docs.projectdiscovery.io/templates/introduction">ドキュメント</a> •
  <a href="#-contributions">貢献</a> •
  <a href="#-discussion">ディスカッション</a> •
  <a href="#-community">コミュニティ</a> •
  <a href="https://docs.projectdiscovery.io/templates/faq">FAQs</a> •
  <a href="https://discord.gg/projectdiscovery">Discordに参加</a>
</p>

<p align="center">
  <a href="https://github.com/projectdiscovery/nuclei-templates/blob/master/README.md">English</a> •
  <a href="https://github.com/projectdiscovery/nuclei-templates/blob/master/README_KR.md">한국어</a> •
  <a href="https://github.com/projectdiscovery/nuclei-templates/blob/master/README_JP.md">日本語</a>
</p>

----

テンプレートは、実際のスキャンエンジンを動作させる[nucleiスキャナー](https://github.com/projectdiscovery/nuclei)のコアです。
このリポジトリは、私たちのチームが提供するテンプレートや、コミュニティからの貢献によるさまざまなテンプレートを保存・管理します。
テンプレートのリストを増やすために、**プルリクエスト**や[Github issues](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+)を通じて貢献していただけると幸いです。

## Nuclei テンプレートの概要

Nucleiテンプレートプロジェクトの概要であり、ユニークなタグ、著者、ディレクトリ、重大度、テンプレートの種類に関する統計を含みます。以下の表は、各マトリックスのトップ10の統計を示しています。拡張バージョンは[こちら](TEMPLATES-STATS.md)で確認でき、[JSON](TEMPLATES-STATS.json)形式でも利用可能です。

<table>
<tr>
<td>

### 🚨 既知の悪用される脆弱性 (KEV) カバレッジ

Nucleiテンプレートは、野生で積極的に悪用されている脆弱性のカバレッジを提供します:

| **KEV ソース** | **テンプレート数** | **説明** |
|----------------|---------------|-----------------|
| 🔴 **CISA KEV** | **454** | [CISA 既知の悪用される脆弱性カタログ](https://www.cisa.gov/known-exploited-vulnerabilities-catalog) |
| 🟠 **VulnCheck KEV** | **1449** | [VulnCheck KEV](https://vulncheck.com/kev) - 強化された脆弱性インテリジェンス |
| 🟢 **両方のソース** | **407** | 両方のカタログの脆弱性をカバーするテンプレート |

> 💡 **ユニークKEVテンプレート総数: 1496** - `nuclei -tags kev,vkev` を使用して積極的に悪用されている脆弱性をスキャン

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

📖 ドキュメント
-----

新しいテンプレートやカスタムテンプレートを作成するための詳細なドキュメントは、https://nuclei.projectdiscovery.io で確認できます。作業方法を理解するためのテンプレートも用意しています。

💪 貢献
-----

Nucleiテンプレートはコミュニティの貢献によって動作します。
[テンプレートの貢献](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+)、[機能リクエスト](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=feature_request.md&title=%5BFeature%5D+)、[バグ報告](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=bug_report.md&title=%5BBug%5D+)はいつでも歓迎します。

![Alt](https://repobeats.axiom.co/api/embed/55ee65543bb9a0f9c797626c4e66d472a517d17c.svg "Repobeats analytics image")

💬 ディスカッション
-----

質問、疑問、アイデアを話し合いたいですか？
[Github discussions](https://github.com/projectdiscovery/nuclei-templates/discussions)で自由に始めることができます。

👨‍💻 コミュニティ
-----

プロジェクトの管理者と直接話し合い、セキュリティや自動化に関することを他の人と共有するために、[Discord Community](https://discord.gg/projectdiscovery)に参加することを歓迎します。さらに、Nucleiに関するすべての情報を更新するために、[Twitter](https://twitter.com/pdnuclei)をフォローすることもできます。

<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=projectdiscovery/nuclei-templates&max=300">
</a>
</p>

皆さんの貢献とコミュニティの活性化への努力に感謝します。
:heart:
