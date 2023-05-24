

<h1 align="center">
Nuclei 템플릿
</h1>
<h4 align="center">커뮤니티에서 엄선한, 애플리케이션 보안 취약점을 찾기 위한 Nuclei 엔진용 템플릿 목록</h4>


<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/issues"><img src="https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat"></a>
<a href="https://github.com/projectdiscovery/nuclei-templates/releases"><img src="https://img.shields.io/github/release/projectdiscovery/nuclei-templates"></a>
<a href="https://twitter.com/pdnuclei"><img src="https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter"></a>
<a href="https://discord.gg/projectdiscovery"><img src="https://img.shields.io/discord/695645237418131507.svg?logo=discord"></a>
</p>
      
<p align="center">
  <a href="https://nuclei.projectdiscovery.io/templating-guide/">가이드 문서</a> •
  <a href="#-contributions">기여</a> •
  <a href="#-discussion">논의</a> •
  <a href="#-community">커뮤니티</a> •
  <a href="https://nuclei.projectdiscovery.io/faq/templates/">FAQs</a> •
  <a href="https://discord.gg/projectdiscovery">디스코드</a>
</p>

<p align="center">
  <a href="https://github.com/projectdiscovery/nuclei-templates/blob/master/README.md">English</a> •
  <a href="https://github.com/projectdiscovery/nuclei-templates/blob/master/README_KR.md">한국어</a>
</p>

----

템플릿은 실제 스캐닝 엔진을 동작하게 하는 [nuclei scanner](https://github.com/projectdiscovery/nuclei)의 핵심입니다.
이 저장소는 우리 팀에서 제공하거나, 커뮤니티에서 기여한 다양한 템플릿들을 저장하고 보관합니다.
템플릿 목록을 증가시키기 위해서 **pull requests** 나 [Github issues](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) 를 통해 기여해주시기를 부탁드립니다.

## Nuclei 템플릿 개요

고유 태그, 작성자, 디렉토리, 심각도, 템플릿 종류에 대한 통계를 포함하고 있는 nuclei 템플릿의 개요입니다. 아래 표는 각 지표의 상위 10개 항목을 나타내고 있습니다. 더 자세한 정보는 [이곳](TEMPLATES-STATS.md)에서 확인 가능하고, [JSON](TEMPLATES-STATS.json) 형식으로도 확인 가능합니다. 

<table>
<tr>
<td> 

## Nuclei 템플릿 통계 Top 10

|    태그    | 개수 |    작성자     | 개수 |    디렉토리     | 개수 | 심각도 | 개수 |  종류   | 개수 |
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

**286개 디렉토리, 4012개 파일**.

</td>
</tr>
</table>

📖 문서
-----

새 템플릿이나 사용자 정의 템플릿을 빌드하기 위한 자세한 문서는 https://nuclei.projectdiscovery.io 에서 확인할 수 있습니다. 작업 방식의 이해를 돕기 위한 템플릿들도 있습니다.

💪 기여
-----

Nuclei 템플릿은 커뮤니티의 기여로 동작합니다.
[템플릿 기여](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+), [기능 요청](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=feature_request.md&title=%5BFeature%5D+), [버그 제보](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=bug_report.md&title=%5BBug%5D+)는 언제든지 환영합니다.

![Alt](https://repobeats.axiom.co/api/embed/55ee65543bb9a0f9c797626c4e66d472a517d17c.svg "Repobeats analytics image")

💬 논의
-----

같이 이야기하고 싶은 질문, 의문 혹은 아이디어가 있으신가요?
[Github discussions](https://github.com/projectdiscovery/nuclei-templates/discussions) 에서 자유롭게 시작할 수 있습니다.

👨‍💻 커뮤니티
-----

프로젝트 관리자와 직접 논의하고 보안과 자동화 관련 사항을 다른 사람과 공유하기 위해 [Discord Community](https://discord.gg/projectdiscovery) 에 참여하는 것을 환영합니다. 추가로 Nuclei 에 대한 모든 정보를 업데이트 하기 위해 [트위터](https://twitter.com/pdnuclei) 팔로우를 할 수 있습니다.

<p align="center">
<a href="https://github.com/projectdiscovery/nuclei-templates/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=projectdiscovery/nuclei-templates&max=300">
</a>
</p>

여러분의 기여와 커뮤니티의 활성화를 위한 노력에 다시한번 감사드립니다.
:heart:
