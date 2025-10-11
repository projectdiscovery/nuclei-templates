# Nuclei 기여 가이드
템플릿은 실제 스캐닝 엔진을 구동하는 [nuclei 스캐너](https://github.com/projectdiscovery/nuclei)의 핵심 요소입니다. Nuclei 템플릿 저장소는 저희 팀과 여러 기여자분들이 TCP, DNS, HTTP, SSL, File, Whois, Websocket, Headless 등 다양한 프로토콜을 위한 템플릿을 저장하고 관리하는 공간입니다.

현재까지 **800명** 이상의 보안 연구원 및 엔지니어분들이 기여해주셨고, **9000개** 이상의 템플릿을 보유하고 있습니다. 저희는 여러분이 **풀 리퀘스트(pull requests)** 나 [깃허브 이슈(Github issues)](https://www.google.com/search?q=https://github.com/projectdiscovery/nuclei-templates/issues/new%3Fassignees%3D%26labels%3D%26template%3Dsubmit-template.md%26title%3D%255Bnuclei-template%255D%2B)를 통해 새로운 템플릿을 제출하여 이 목록을 더욱 풍성하게 만들어 주시기를 바랍니다. 이러한 기여 활동은 커뮤니티에 도움을 줄 뿐만 아니라 **❤️**, 여러분 스스로도 경험을 쌓고 동료들에게 인정받으며 커리어에도 긍정적인 영향을 줄 수 있습니다.

이 문서는 여러분의 기여 과정을 돕기 위한 가이드라인입니다. 저희는 이 프로젝트에 새로운 **템플릿을 추가**하거나 기존 템플릿을 **개선**하고자 하는 모든 분들의 기여를 기쁘게 생각합니다. 도움을 주셔서 감사드리며, **아무리 작은 기여라도 소중하다는 점**을 기억해 주세요.

## **어떻게 기여할 수 있나요?**

  - [Nuclei 템플릿 제출하기](https://www.google.com/search?q=%23Submitting-Nuclei-Templates)
  - [미탐(False Negative) 템플릿 보고하기](https://www.google.com/search?q=%23Reporting-False-Positive-Template)
  - [오탐(False Positive) 템플릿 보고하기](https://www.google.com/search?q=%23Reporting-False-Positive-Template)
  - [기존 템플릿 개선하기](https://www.google.com/search?q=%23Enhancing-existing-templates)
  - [유효하지 않은 템플릿 보고하기](https://www.google.com/search?q=%23Reporting-Invalid-templates)
  - [템플릿 요청하기](https://www.google.com/search?q=%23Request-Template)
  - [nuclei-templates를 위한 아이디어/기능 공유하기](https://www.google.com/search?q=%23Sharing-idea-/-feature-for-nuclei-templates)

### **Nuclei 템플릿 제출하기**

**이슈나 풀 리퀘스트를 제출하기 전에**

  - 새로운 템플릿을 만들기 전, [기존 템플릿](https://github.com/projectdiscovery/nuclei-templates) 목록을 확인하거나 엔드포인트를 검색해 보세요.
  - 중복 작업을 피하기 위해 [깃허브 이슈](https://github.com/projectdiscovery/nuclei-templates/issues)와 [풀 리퀘스트](https://github.com/projectdiscovery/nuclei-templates/pulls)에 이미 동일한 내용이 있는지 확인해 보세요.
  - 새로운 템플릿을 만들 때는 [템플릿 제작 가이드](https://www.google.com/search?q=TEMPLATE-CREATION-GUIDE.md)와 [Matcher 가이드라인](https://github.com/projectdiscovery/nuclei-templates/wiki/How-to-Write-Unique-Matchers-in-Nuclei-Templates)을 참고해 주세요.

새로운 템플릿을 제출할 때는 P.O.C(취약점 증명) 코드와 함께 `info` 섹션에 아래 필드들을 필수로 포함해야 합니다.

1.  `id`: 가급적 3\~4 단어 이내의 짧은 이름으로 지정해야 합니다. 예: `grafana-unauth-rce`
2.  `name`: 이름은 `<제조사> <제품명> <버전> - <취약점>` 형식에 맞춰 간결하게 작성해야 합니다.
3.  `author`: 깃허브/트위터 사용자명이나 별명을 사용할 수 있습니다. ([https://github.com/projectdiscovery/nuclei-templates/blob/main/contributors.json](https://github.com/projectdiscovery/nuclei-templates/blob/main/contributors.json)) 파일에 PR을 생성하여 작성자 이름과 관련된 더 자세한 정보를 추가할 수도 있습니다.
4.  `severity`: CVSS 점수를 기반으로 하지만, 실제 공격 성공 가능성이나 영향도에 따라 달라질 수 있습니다.
5.  `description`: 취약점에 대한 간략한 설명입니다.
6.  `reference`: 저희 팀이 템플릿을 검증하는 데 도움이 되도록 P.O.C, 환경 설정 가이드, 제품 정보 등에 대한 참조 링크를 제공해 주세요.

**권장 사항(Do’s)**

  - 템플릿 검증을 완료했다면, `metadata` 필드에 `verified: true`로 표시하고, PR에 취약한 서버 정보를 가린 후 `-debug` 플래그를 사용하여 얻은 디버그 데이터를 공유해 주세요.
  - 오탐(False Positive)을 방지하기 위해 하나 이상의 Matcher를 추가해야 합니다. 응답 결과 어디에서나 쉽게 발견될 수 있는 짧은 단어는 피하는 것이 좋습니다.
  - 가능하다면 docker-compose 기반의 취약한 환경을 함께 제출해 주세요. 예: [https://github.com/vulhub/vulhub](https://github.com/vulhub/vulhub)
  - 저희는 단순 버전 탐지가 아닌, 완전한 P.O.C가 포함된 템플릿만 허용합니다.

**지양 사항(Don’t)**

  - PR에 실제 운영 중인 서비스의 주소를 공유하지 마세요. 만약 취약한 테스트 환경을 직접 구축했다면, 저희 팀이 템플릿을 쉽게 검증할 수 있도록 디스코드(Discord)를 통해 개인적으로 공유해 주세요.
  - 취약한 Matcher를 사용한 템플릿 제출은 피해야 합니다. 예를 들어, GET/POST 요청 데이터를 Matcher로 추가하는 경우 일부 호스트에서 오탐을 유발할 수 있습니다.
  - 기존 요청이나 경로만으로도 버그 존재 여부를 충분히 확인할 수 있는데, 불필요하게 요청을 추가하는 등 기존 템플릿을 과도하게 변경하지 마세요.
  - 템플릿당 요청 수는 가능한 한 적게 유지해 주세요.

**모범 사례(Best Practices)**

  - 템플릿을 올바른 디렉터리에 추가했는지 확인해 주세요.
  - Matcher에 `part`를 명시해 주세요. 예를 들어 Matcher가 응답 본문(body)에 있다면 `part: body`를 추가합니다.
  - RCE(원격 코드 실행) 템플릿의 경우, 저장소 전체의 통일성을 위해 `cmd` 변수를 사용해 주세요.
  - 모든 인증 기반 템플릿에는 `{{username}}`과 `{{password}}` 변수를 사용해 주세요.
  - 키(key)나 토큰(token)을 다루는 모든 템플릿에는 `{{token}}` 변수를 사용해 주세요.
  - 특정 기술에 대한 템플릿이 2개 이상이라면, 별도의 폴더를 만들어 관리해 주세요.
  - 취약한 URL은 깃허브나 디스코드 채널에 공개적으로 공유하지 마세요.
  - 웹 셸 업로드는 취약점을 검증하기 위한 최후의 수단으로만 사용해야 하며, 파일을 업로드할 경우 파일명은 반드시 무작위 문자열(`{{randstr}}`)로 생성되도록 해야 합니다.
  - HTTP나 JavaScript로 작성 가능한 공격 코드를 별도의 코드 템플릿으로 만들지 마세요. 예외적인 경우가 아니라면 프로젝트에 불필요한 공격 코드를 추가하는 것은 지양합니다.

### **PR(풀 리퀘스트) 제출하기**

**프로젝트 Fork 하기**

  - 이 저장소를 여러분의 깃허브 프로필에 로컬 복사본으로 만듭니다. 원본 프로젝트는 `upstream` 원격 저장소로 참조를 유지합니다.

\<img width="928" alt="template-fork" src="[https://user-images.githubusercontent.com/8293321/124467966-2afde200-ddb6-11eb-835f-8f8fc2fabedb.png](https://user-images.githubusercontent.com/8293321/124467966-2afde200-ddb6-11eb-835f-8f8fc2fabedb.png)"\>

```jsx
git clone https://github.com/<your-username>/nuclei-templates
cd nuclei-templates
git remote add upstream https://github.com/projectdiscovery/nuclei-templates
```

  - 만약 이미 프로젝트를 fork 했다면, 작업을 시작하기 전에 최신 버전으로 업데이트하세요.

<!-- end list -->

```jsx
git remote update
git checkout main
git rebase upstream/main
```

**템플릿 브랜치(Branch) 생성하기**

  - 새로운 브랜치를 생성합니다. 브랜치 이름은 여러분이 해결하려는 이슈를 식별할 수 있도록 지어주세요.

<!-- end list -->

```jsx
# template_branch_name이라는 새 브랜치를 만들고 해당 브랜치로 전환합니다.
git checkout -b template_branch_name
```

**템플릿 생성 및 커밋(Commit)하기**

  - 템플릿을 만듭니다.
  - 필요한 모든 파일/폴더를 추가합니다.
  - 변경 사항을 적용했거나 템플릿 제작을 완료했다면, 방금 만든 브랜치에 변경 사항을 추가합니다.

<!-- end list -->

```jsx
# 모든 새 파일을 template_branch_name 브랜치에 추가합니다.
git add .
```

  - 커밋할 때는 리뷰어가 이해하기 쉽도록 내용을 설명하는 메시지를 작성합니다.

<!-- end list -->

```jsx
# 이 메시지는 변경한 모든 파일과 연결됩니다.
git commit -m "Added/Fixed/Updated XXX Template"
```

**참고**:

  - 하나의 풀 리퀘스트에는 가급적 하나의 템플릿만 포함해 주세요. 이렇게 하면 저희가 리뷰하기 더 간단하며, 다른 템플릿 때문에 PR 전체가 보류되는 상황을 막을 수 있습니다.
  - 동일한 기술에 대한 여러 템플릿은 하나의 풀 리퀘스트로 묶을 수 있습니다.

**변경 사항 Push 하기**

  - 이제 여러분이 작업한 템플릿을 원격(fork한) 저장소로 push 할 준비가 되었습니다.
  - 작업이 완료되고 프로젝트 규칙을 준수한다면, 변경 사항을 여러분의 fork 저장소에 업로드하세요.

<!-- end list -->

```jsx
# 작업 내용을 원격 저장소로 push 합니다.
git push -u origin template_branch_name
```

**풀 리퀘스트(Pull Request) 생성하기**

  - 웹 브라우저를 열어 여러분의 깃허브 저장소로 이동한 뒤, `Pull requests` 탭에서 `New pull request` 버튼을 클릭하세요. 여러분의 풀 리퀘스트에 템플릿의 목적을 설명하는 의미 있는 이름과 설명을 제공해 주세요.
  - 이제 여러분의 풀 리퀘스트가 제출되었습니다\! 관리자가 프로젝트 표준에 부합하는지 검토한 후 병합(merge)하거나 피드백을 제공할 것입니다.🥳

### [미탐(False Negative) 템플릿](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-negative.yml) 보고하기

유효하거나 기대했던 결과를 놓치는 템플릿에 대해 이슈나 PR을 생성하여 프로젝트에 기여할 수 있습니다.

  - 사용 중인 nuclei 버전과 해당 템플릿의 경로를 공유해 주세요.
  - 템플릿이 취약한 대상을 탐지하지 못하는 경우, 해당 호스트에 대한 `-debug` 데이터를 공유해 주세요.
  - 가능하다면 개선된 Matcher나 올바른 Matcher, 관련 자료, 그리고 취약한 환경을 설정하는 데 필요한 정보를 공유해 주세요.

> 참고: 호스트 정보를 공개적으로 공유할 수 없는 경우, 디스코드 서버에서 저희에게 DM으로 연락해 주세요.
>  

**[미탐(False negative) 이슈](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-negative.yml) 생성 또는 PR 제출하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - **`False Negative`** 항목 옆의 `get started`를 클릭하세요.

### [오탐(False Positive) 템플릿](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-positive.yml) 보고하기

유효하지 않거나 예상치 못한 결과를 내는 템플릿에 대해 이슈나 PR을 생성하여 프로젝트에 기여할 수 있습니다.

  - 사용 중인 nuclei 버전과 해당 템플릿의 경로를 공유해 주세요.
  - 템플릿이 취약하지 않은 대상을 탐지하여 유효하지 않은 결과를 내는 경우, `-debug` 데이터와 가능하다면 해당 호스트 정보를 공유해 주세요.
  - 가능하다면 개선된 Matcher나 올바른 Matcher, 그리고 취약점 관련 자료를 공유해 주세요.

**[오탐(False positive) 이슈](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-positive.yml) 생성 또는 PR 제출하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - **`False Positive`** 항목 옆의 `get started`를 클릭하세요.

### 기존 템플릿 개선하기

디렉터리 구조 변경, 템플릿에 새로운 카테고리나 필드 추가 등 nuclei-templates 저장소를 개선하기 위한 이슈나 PR을 생성하여 프로젝트에 기여할 수 있습니다.

개선이 필요한 이유나 요구 사항, 그리고 이를 통해 템플릿의 전반적인 품질을 어떻게 향상시킬 수 있는지 공유해 주세요.

**개선 사항 제안을 위한 이슈 생성 또는 PR 제출하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - `Enhancement request` 항목 옆의 `get started`를 클릭하세요.

### 유효하지 않은 템플릿 보고하기

유효하지 않은 템플릿을 발견했거나, 저장소의 특정 템플릿이 예기치 않은 오류를 발생시킨다면 유효하지 않은 템플릿으로 보고해 주세요. 다음 정보를 반드시 제공해야 합니다.

  - 사용 중인 nuclei 버전과 해당 템플릿의 경로를 공유해 주세요.
  - 오류가 포함된 스크린샷과 `-verbose` 출력 결과를 공유하고, 해당하는 경우 `-debug` 플래그를 사용한 디버그 데이터도 제공해 주세요.
  - 만약 이 문제가 특정 환경에서만 발생하고 다른 환경에서는 발생하지 않는다면, 사용 중인 OS와 설정에 대한 자세한 정보를 제공해 주세요.

**유효하지 않은 템플릿 보고를 위한 이슈 생성하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - `Report Issue` 항목 옆의 `get started`를 클릭하세요.

### 템플릿 요청하기

특정 취약점이나 새로운 CVE에 대한 P.O.C 자료가 있다면, 이슈를 생성하여 템플릿 제작을 요청할 수 있으며 저희 팀이 제작해 드립니다. 다음 정보를 반드시 제공해야 합니다.

  - 완전한 P.O.C가 포함된 취약점 관련 자료
  - 가능하다면 취약한 도커(docker) 이미지나 취약한 환경을 설정하는 단계 공유

> 참고: 취약한 환경을 직접 구축했다면, 디스코드 서버에서 저희 팀에게 DM으로 호스트 정보를 공유할 수 있습니다.
>  

**nuclei 템플릿 요청을 위한 이슈 생성하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - `Request Template` 항목 옆의 `get started`를 클릭하세요.

### nuclei-templates를 위한 아이디어/기능 공유하기

nuclei-templates에 대한 아이디어가 있거나 기능을 요청하고 싶다면, 새로운 토론(discussion)을 만들어 공유할 수 있습니다.

**아이디어/기능 공유를 위한 토론 생성하기**

  - `Issues` 탭을 클릭한 후 `new issue`를 클릭하세요.
  - `Share idea / feature to discuss for nuclei-templates` 항목 옆의 `open`을 클릭하세요.
