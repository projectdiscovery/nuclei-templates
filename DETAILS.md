# DETAILS.md

---


🔍 **Powered by [Detailer](https://detailer.ginylil.com)** - Advanced AI repository mapping

## Project Overview

### Purpose & Domain

This project is a comprehensive, modular, and extensible security testing and vulnerability management framework. It provides declarative, data-driven definitions for a wide range of security concerns, including:

- **Vulnerability detection**: CVE and CNVD-based HTTP attack signatures for web applications and services.
- **Cloud security posture**: Compliance and misconfiguration checks across major cloud providers (AWS, Azure, GCP, Alibaba).
- **Static code analysis**: Secret detection rules for various platforms and services.
- **Malware detection**: Signature-based malware identification using YARA-like rules and hash-based signatures.
- **Privilege escalation detection**: Linux and Windows privilege escalation test definitions.
- **Kubernetes security**: Policies and compliance checks for deployments, pods, network policies, and cluster configurations.
- **Dynamic application security testing (DAST)**: Attack payloads and detection rules for vulnerabilities like SSTI, XSS, command injection, and credential stuffing.
- **Headless testing**: Automated browser-based security tests and reconnaissance workflows.
- **Log analysis**: Pattern matching for suspicious or exception log entries across multiple frameworks.
- **Webshell detection**: Signature definitions for detecting malicious webshells in various languages.

### Target Users & Use Cases

- **Security researchers and penetration testers**: Utilize the extensive CVE and vulnerability definitions for automated scanning and exploit verification.
- **DevSecOps teams**: Integrate compliance and misconfiguration checks into CI/CD pipelines to enforce security best practices.
- **Cloud security teams**: Monitor cloud environments for misconfigurations, insecure settings, and compliance violations.
- **Static analysis engineers**: Detect hardcoded secrets and sensitive tokens in codebases.
- **Malware analysts**: Use signature-based detection rules to identify malware samples.
- **Kubernetes administrators**: Enforce security policies and audit cluster configurations.
- **Security automation engineers**: Leverage headless testing templates for automated vulnerability discovery.

### Value Proposition

- **Modularity & Extensibility**: The framework is organized into modular YAML files, enabling easy addition, removal, or update of security rules without modifying core code.
- **Declarative & Data-Driven**: Security rules and test cases are defined declaratively, facilitating automation and integration with various scanning engines.
- **Cross-Platform & Multi-Cloud Support**: Provides comprehensive coverage across cloud providers, operating systems, and application platforms.
- **Comprehensive Security Coverage**: From low-level privilege escalation to high-level web application vulnerabilities and cloud misconfigurations.
- **AI & Automation Friendly**: Structured data and templated configurations enable AI agents and automation tools to efficiently parse, understand, and execute security assessments.

---

## Architecture and Structure

### Complete Repository Structure

```
.
├── .github/ (29 items)
│   ├── ISSUE_TEMPLATE/
│   │   ├── config.yml
│   │   ├── false-negative.yml
│   │   ├── false-positive.yml
│   │   ├── template-contribution.yml
│   │   └── template-requests.yml
│   ├── scripts/ (9 items)
│   │   ├── yaml2json/
│   │   │   ├── go.mod
│   │   │   ├── go.sum
│   │   │   └── main.go
│   │   ├── README.tmpl
│   │   ├── assign_tasks.py
│   │   ├── update-readme.py
│   │   ├── weak-matcher-checks.sh
│   │   └── wordpress-plugins-update.py
│   ├── workflows/ (10 items)
│   │   ├── autoassign.yml
│   │   ├── cve2json.yml
│   │   ├── new-templates.yml
│   │   ├── readme-update.yml
│   │   ├── template-checksum.yml
│   │   ├── template-sign.yml
│   │   ├── templateman.yml
│   │   ├── templates-stats.yml
│   │   ├── tests.yml
│   │   └── wordpress-plugins-update.yml
│   ├── auto_assign.yml
│   └── dependabot.yml
├── cloud/ (748 items)
│   ├── alibaba/ (53 items)
│   │   ├── ack/ (7 items)
│   │   ├── actiontrail/
│   │   ├── ecs/
│   │   ├── oss/ (7 items)
│   │   ├── ram/ (10 items)
│   │   ├── rds/ (9 items)
│   │   ├── security-center/
│   │   ├── vpc/
│   │   └── alibaba-cloud-code-env.yaml
│   ├── aws/ (171 items)
│   │   ├── acm/
│   │   ├── cloudformation/
│   │   ├── cloudfront/ (10 items)
│   │   ├── cloudtrail/ (12 items)
│   │   ├── cloudwatch/
│   │   ├── aws-code-env.yaml
│   │   └── ... (18 more directories)
│   ├── azure/ (215 items)
│   │   ├── accesscontrol/
│   │   ├── activedirectory/
│   │   ├── activitylog/ (29 items)
│   │   ├── aiservices/
│   │   ├── aks/ (10 items)
│   │   ├── azure-env.yaml
│   │   └── ... (19 more directories)
│   ├── enum/ (9 items)
│   ├── gcp/ (249 items)
│   │   ├── api/ (6 items)
│   │   ├── artifactregistry/
│   │   ├── bigquery/
│   │   ├── cdn/
│   │   ├── certificatemanager/
│   │   ├── gcp-env.yaml
│   │   └── ... (18 more directories)
│   └── kubernetes/ (45 items)
│       ├── cves/
│       ├── deployments/ (14 items)
│       ├── network-policies/
│       ├── pods/ (7 items)
│       ├── rbac/
│       ├── security-compliance/ (9 items)
│       ├── kubernetes-code-env.yaml
│       └── kubernetes-exposing-docker-socket-hostpath.yaml
├── dast/ (275 items)
│   ├── cves/ (10 items)
│   ├── vulnerabilities/ (263 items)
│   ├── ... (other directories)
├── dns/ (23 items)
│   ├── azure-takeover-detection.yaml
│   ├── bimi-detect.yaml
│   ├── caa-fingerprint.yaml
│   ├── detect-dangling-cname.yaml
│   ├── dmarc-detect.yaml
│   ├── dns-rebinding.yaml
│   ├── dns-saas-service-detection.yaml
│   ├── dns-waf-detect.yaml
│   ├── dnssec-detection.yaml
│   ├── ec2-detection.yaml
│   └── ... (13 more files)
├── file/ (496 items)
│   ├── android/ (16 items)
│   ├── audit/ (59 items)
│   ├── bash/
│   ├── electron/
│   ├── js/
│   ├── keys/ (191 items)
│   ├── logs/ (55 items)
│   ├── malware/ (190 items)
│   ├── nodejs/ (6 items)
│   ├── perl/
│   ├── php/
│   ├── python/
│   ├── url-analyse/
│   ├── webshell/
│   └── xss/
├── headless/ (28 items)
│   ├── cves/
│   ├── technologies/
│   ├── vulnerabilities/
│   ├── cookie-consent-detection.yaml
│   ├── dvwa-headless-automatic-login.yaml
│   ├── extract-urls.yaml
│   ├── headless-open-redirect.yaml
│   ├── postmessage-outgoing-tracker.yaml
│   ├── postmessage-tracker.yaml
│   ├── prototype-pollution-check.yaml
│   ├── screenshot.yaml
│   ├── webpack-sourcemap.yaml
│   └── window-name-domxss.yaml
├── helpers/ (28 items)
│   └── payloads/ (27 items)
│       ├── cve-2023-34039-keys/ (22 items)
│       ├── CVE-2018-25031.js
│       ├── CVE-2020-5776.csv
│       ├── retool-xss.svg
│       └── swagger-payload
├── http/ (9431 items)
│   ├── cnvd/ (52 items)
│   ├── credential-stuffing/ (17 items)
│   ├── cves/ (3154 items)
│   ├── ... (other directories)
├── javascript/ (100 items)
│   ├── backdoor/
│   ├── cves/
│   ├── default-logins/
│   ├── detection/
│   ├── enumeration/
│   ├── exposures/
│   ├── honeypot/
│   ├── jarm/
│   ├── misconfiguration/
│   └── udp/
├── network/ (166 items)
│   ├── backdoor/
│   ├── c2/
│   ├── cves/
│   ├── default-login/
│   ├── detection/
│   ├── enumeration/
│   ├── exposures/
│   ├── honeypot/
│   ├── jarm/
│   ├── misconfiguration/
│   └── udp/
├── profiles/ (20 items)
├── ssl/ (40 items)
├── technologies/ (19 items)
├── workflows/ (202 items)
├── .gitignore
├── .nuclei-ignore
├── .pre-commit-config.yml
├── .review-bot
├── .yamllint
├── CODE_OF_CONDUCT.md
├── CONTRIBUTING.md
├── Community-Rewards-FAQ.md
├── LICENSE.md
├── PULL_REQUEST_TEMPLATE.md
├── README.md
├── README_CN.md
├── README_JA.md
├── README_KR.md
├── TEMPLATES-STATS.json
├── TEMPLATES-STATS.md
├── TOP-10.md
├── contributors.json
├── cves.json
└── wappalyzer-mapping.yml
```

---

### Directory Structure Context

- The repository is organized by **functional domains**:
  - `.github/`: CI/CD workflows, issue templates, scripts.
  - `cloud/`: Cloud provider-specific security checks and compliance rules.
  - `dast/`: Dynamic Application Security Testing payloads and vulnerability definitions.
  - `dns/`: DNS security and detection rules.
  - `file/`: Static analysis rules for various platforms and file types.
  - `headless/`: Headless browser testing templates and CVE tests.
  - `http/`: HTTP-based vulnerability signatures and credential stuffing modules.
  - `javascript/`, `network/`: Detection rules for specific technologies and network protocols.
  - `profiles/`, `ssl/`, `technologies/`, `workflows/`: Configuration and orchestration files.
  - Root-level files for documentation, configuration, and metadata.

---

## Technical Implementation Details

### Rule Definition Format

- **YAML-based declarative format** is used extensively for defining:
  - Vulnerability signatures (`http/cves/`).
  - Cloud compliance checks (`cloud/`).
  - Static analysis rules (`file/keys/`, `file/malware/`).
  - Dynamic attack payloads (`dast/`).
  - Headless browser testing workflows (`headless/`).

- **Common Sections**:
  - `id`: Unique identifier for the rule or test.
  - `info`: Metadata including name, author, severity, description, references, tags.
  - `http`: HTTP request definitions with methods, paths, headers, bodies.
  - `matchers`: Conditions to validate responses (regex, word, status, DSL).
  - `extractors`: Data extraction rules from responses or files.
  - `flow`: Defines execution order or logical combination of tests.
  - `variables`: Parameterization for dynamic payloads or URLs.
  - `code`: Embedded scripts or commands (shell, PowerShell, Python).

### Cloud Compliance Checks

- **Cloud provider directories** (`cloud/aws/`, `cloud/azure/`, `cloud/gcp/`, `cloud/alibaba/`) contain:
  - Modular YAML files defining security policies.
  - Use of CLI commands (`aws`, `az`, `gcloud`, `aliyun`) embedded in `code` blocks.
  - Matchers and extractors to parse CLI outputs.
  - Iterative flows over resources (instances, buckets, clusters).
  - Metadata for severity, remediation, and references.

### Static Analysis Rules

- **Secret detection** (`file/keys/`):
  - Regex-based extractors targeting API keys, tokens, and secrets for various services.
  - Metadata includes severity, author, references.
  - Designed for integration with static analysis tools like Semgrep or Gitleaks.

- **Malware detection** (`file/malware/`):
  - Signature-based detection using string and binary matchers.
  - Hash-based detection using SHA-256 signatures in `file/malware/hash/`.
  - Metadata for classification and references.

- **Privilege escalation** (`code/privilege-escalation/`):
  - YAML files defining test commands and matchers for Linux and Windows privilege escalation techniques.
  - Use of shell and PowerShell scripts embedded in YAML.
  - Matchers validate command outputs for success.

### Dynamic Application Security Testing (DAST)

- **DAST payloads and detection rules** (`dast/`):
  - Attack payloads for SSTI, XSS, command injection, credential stuffing.
  - Use of templated payloads with fuzzing strategies.
  - Matchers and DSL expressions for detection.
  - Headless browser automation for dynamic testing.

### Headless Testing

- **Headless browser testing templates** (`headless/`):
  - YAML files defining sequences of browser actions (navigate, click, wait).
  - Matchers and extractors for DOM and network response analysis.
  - Used for automated security testing and reconnaissance.

### HTTP Vulnerability Signatures

- **HTTP CVE definitions** (`http/cves/`):
  - Structured HTTP requests with matchers to detect known vulnerabilities.
  - Use of templated URLs and payloads.
  - Logical flow control for multi-step tests.
  - Metadata for severity, references, and classification.

---

## Development Patterns and Standards

- **Declarative Configuration**: The project heavily relies on YAML for defining rules, tests, and workflows, promoting separation of concerns and ease of updates.
- **Modularity**: Each security check, vulnerability, or detection rule is encapsulated in its own YAML file, enabling independent development and maintenance.
- **Templating & Parameterization**: Use of placeholders (`{{BaseURL}}`, `{{username}}`, `{{randstr}}`) allows dynamic test generation and environment-specific configurations.
- **Pattern Matching**: Extensive use of regex, word matching, and DSL expressions for flexible and precise detection.
- **Iterative Flows**: Use of `flow` blocks and iteration constructs (`iterate()`) to process multiple resources or test steps.
- **Embedded Scripting**: Shell, PowerShell, Python, and JavaScript snippets embedded within YAML for complex detection or test logic.
- **Metadata Richness**: Each rule/test includes detailed metadata for severity, impact, remediation, references, and classification, supporting reporting and prioritization.
- **Versioning & Integrity**: Use of digest hashes (commented or active) for rule integrity and version control.

---

## Integration and Dependencies

### External Dependencies

- **Cloud Provider CLIs**: `aws`, `az`, `gcloud`, `aliyun` for cloud compliance checks.
- **Security Tools**: Integration with YARA, Semgrep, Gitleaks for malware and secret detection.
- **HTTP Clients & Browsers**: Underlying HTTP libraries and headless browsers (e.g., Puppeteer, Selenium) for DAST and headless testing.
- **Regex Engines**: For pattern matching in response bodies, headers, and files.
- **Templating Engines**: For dynamic variable substitution in test definitions.

### Internal Dependencies

- **Rule Engines**: Custom or third-party engines that parse YAML rules and execute detection logic.
- **Test Orchestration Framework**: Manages execution flow, variable injection, and result aggregation.
- **Reporting Modules**: Aggregate findings, generate reports, and integrate with dashboards or alerting systems.
- **Scripting Runtimes**: Shell, PowerShell, Python, JavaScript environments for embedded code execution.

---

## Usage and Operational Guidance

### Understanding the Codebase

- **Rule Files**: Located primarily under `http/cves/`, `cloud/`, `dast/`, `file/keys/`, and `file/malware/`. Each file defines a specific security check or detection rule.
- **Metadata**: Read the `info` section in YAML files for context, severity, and remediation guidance.
- **Templating**: Replace placeholders like `{{BaseURL}}` with target-specific values before execution.
- **Matchers**: Understand the matching logic (regex, word, DSL) to interpret detection results.
- **Flows**: Follow `flow` definitions to comprehend multi-step test sequences.

### Modifying or Extending

- **Add New Rules**: Create new YAML files following existing schema in appropriate directories (`http/cves/`, `file/keys/`, etc.).
- **Update Payloads**: Modify `payloads` or `code` sections for new attack vectors or detection logic.
- **Adjust Matchers**: Refine regex or DSL expressions to improve detection accuracy or reduce false positives.
- **Parameterize**: Use templating variables for dynamic test generation and environment adaptation.
- **Integrate Scripts**: Embed additional shell, PowerShell, or JavaScript snippets as needed for complex detection.

### Running Tests

- **Security Scanner**: Use or develop a scanner that loads these YAML definitions, performs HTTP requests, executes embedded scripts, applies matchers, and reports findings.
- **CI/CD Integration**: Incorporate scanning into pipelines for continuous security validation.
- **Headless Testing**: Utilize headless browser YAML scripts for dynamic vulnerability detection.
- **Static Analysis**: Use secret detection and malware signature YAMLs with static analysis tools.

### Monitoring and Reporting

- **Metadata Usage**: Leverage severity, tags, and references for prioritizing and categorizing findings.
- **Digest Validation**: Use digest hashes to verify rule integrity.
- **Alerting**: Integrate with alerting systems to notify on critical detections.
- **Dashboarding**: Aggregate results for visualization and trend analysis.

---

# Summary

This project is a **comprehensive, modular, and extensible security testing and vulnerability management framework**. It uses **declarative YAML configurations** to define:

- **Vulnerability detection rules** (HTTP CVE signatures, cloud compliance checks).
- **Static analysis rules** (secret detection, malware signatures).
- **Dynamic testing payloads** (DAST, headless browser tests).
- **Privilege escalation detection** (Linux/Windows).
- **Log and webshell detection**.

The architecture emphasizes **separation of concerns**, **data-driven design**, and **modularity**, enabling easy updates and integration with various security tools and automation pipelines. The **complete repository structure** provides a clear map of the project's scope and organization, facilitating navigation and extension.

---

# Actionable Insights

- **For AI Agents**: The YAML files are **self-contained declarative rules** that can be parsed and interpreted independently. Focus on the `id`, `info`, `http`, `matchers`, `extractors`, and `flow` sections for understanding detection logic.
- **For Developers**: To add new detection capabilities, create new YAML files in the appropriate directory following existing schemas. Use templating variables for flexibility.
- **For Security Analysts**: Use metadata fields to prioritize findings and understand remediation steps. Leverage the modular structure to focus on specific vulnerability classes or cloud providers.
- **For Automation Engineers**: Integrate YAML-based rules into scanning pipelines, ensuring templating variables are correctly substituted and matchers are properly evaluated.
- **For Documentation**: Maintain metadata and references in YAML files to ensure traceability and ease of audit.

---

# End of DETAILS.md