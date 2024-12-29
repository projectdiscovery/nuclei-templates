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
  <a href="https://nuclei.projectdiscovery.io/templating-guide/">ドキュメント</a> •
  <a href="#-contributions">貢献</a> •
  <a href="#-discussion">ディスカッション</a> •
  <a href="#-community">コミュニティ</a> •
  <a href="https://nuclei.projectdiscovery.io/faq/templates/">FAQs</a> •
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

## Nuclei テンプレート トップ10統計

|    タグ    | 数   |    著者       | 数   |    ディレクトリ  | 数   | 重大度 | 数   |  種類   | 数   |
|-----------|-------|---------------|-------|------------------|-------|----------|-------|---------|-------|
| cve       |  1325 | daffainfo     |   629 | cves             |  1306 | info     |  1398 | http    |  3644 |
| panel     |   604 | dhiyaneshdk   |   509 | exposed-panels   |   613 | high     |   955 | file    |    76 |
| lfi       |   490 | pikpikcu      |   322 | vulnerabilities  |   506 | medium   |   784 | network |    50 |
| xss       |   451 | pdteam        |   269 | technologies     |   273 | critical |   445 | dns     |    17 |
| wordpress |   409 | geeknik       |   187 | exposures        |   254 | low      |   211 |         |       |
| exposure  |   360 | dwisiswant0   |   169 | token-spray      |   230 | unknown  |     7 |         |       |
| cve2021   |   324 | 0x_akoko      |   157 | misconfiguration |   210 |          |       |         |       |
| rce       |   319 | princechaddha |   149 | workflows        |   187 |          |       |         |       |
| wp-plugin |   304 | pussycat0x    |   130 | default-logins   |   102 |          |       |         |       |
| tech      |   286 | gy741         |   126 | file             |    76 |          |       |         |       |

**286個のディレクトリ、4012個のファイル**。

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
