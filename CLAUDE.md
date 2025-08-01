# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nuclei Templates is a community-curated repository containing 11,246+ security testing templates for the Nuclei scanner. This is the core component that powers vulnerability detection across various protocols (HTTP, DNS, SSL, TCP, etc.) and is maintained by 800+ security researchers.

## Essential Development Commands

### Template Validation
```bash
# Validate all templates
nuclei -duc -validate -lfa -ud $GITHUB_WORKSPACE -w workflows/ -et .github/

# Validate specific template
nuclei -t path/to/template.yaml -validate
```

### Template Testing
```bash
# Test template against target
nuclei -t template.yaml -u https://target.example.com

# Test with debug output
nuclei -t template.yaml -u https://target.example.com -debug
```

### Linting
```bash
# YAML linting (uses .yamllint config)
yamllint .
```

### Template Signing (CI only)
```bash
nuclei -lfa -duc -sign -ud $GITHUB_WORKSPACE -t .
```

## Template Structure and Organization

### Directory Architecture
- `http/` - HTTP-based templates (8,877 templates)
  - `cves/` - CVE-specific vulnerabilities  
  - `exposed-panels/` - Admin panels/interfaces
  - `default-logins/` - Default credentials
  - `vulnerabilities/` - General HTTP vulnerabilities
- `cloud/` - Cloud service templates (AWS, Azure, GCP)
- `code/` - Static code analysis templates
- `file/` - File-based security checks
- `workflows/` - Multi-step scanning sequences
- `profiles/` - Predefined scanning configurations
- `helpers/` - Wordlists and payloads

### Template Naming Conventions
- CVE templates: `CVE-YYYY-NNNNN.yaml`
- Panel detection: `vendor-product-panel.yaml`
- Default logins: `vendor-product-default-login.yaml`
- Vulnerabilities: `vendor-product-vulnerability.yaml`

## Template Format Standards

### Required Template Structure
```yaml
id: unique-template-identifier

info:
  name: "Vendor Product - Description"
  author: github-username
  severity: info|low|medium|high|critical
  description: Clear vulnerability description
  classification:
    cvss-metrics: CVSS:3.0/AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:N/A:N
    cve-id: CVE-YYYY-NNNNN (if applicable)
    cwe-id: CWE-NNN
  metadata:
    verified: true
    max-request: N
  tags: relevant,tags,here

http:
  - method: GET
    path:
      - "{{BaseURL}}/vulnerable/path"
    
    matchers-condition: and
    matchers:
      - type: word
        words: ["specific", "indicators"]
      - type: status
        status: [200]
```

### Essential Template Variables
- `{{BaseURL}}` - Target base URL
- `{{username}}/{{password}}` - Authentication templates
- `{{token}}` - API keys/tokens
- `{{randstr}}` - Random strings for file names
- `cmd` - Command injection payloads

### Quality Requirements
- Multiple matchers to prevent false positives
- `verified: true` metadata for tested templates
- Proper CVSS scoring and CWE classification
- One template per PR (preferred)

## Development Workflow

### Creating New Templates
1. Fork repository and create feature branch
2. Place template in appropriate directory based on type
3. Follow naming conventions and template structure
4. Test template thoroughly before submission
5. Ensure `verified: true` if personally tested

### Template Categories
- **Detection templates** (severity: info) - Identify technologies/panels
- **Vulnerability templates** - Actual security issues requiring action
- **Workflow templates** - Multi-step scanning sequences in `workflows/`

### CI/CD Validation
Templates automatically undergo:
- YAML syntax validation with yamllint
- Nuclei template validation
- Weak matcher detection against honeypot targets
- Template signing and checksum generation

### Contribution Guidelines
- Complete POC required (not just version detection)
- Templates must be tested against real targets when possible
- False positive prevention is critical
- Reference CVE/advisory links when applicable
- Use appropriate severity levels

## Template Profiles

Pre-configured template sets available in `profiles/`:
- `recommended.yml` - Curated general-purpose templates
- `pentest.yml` - Penetration testing focused
- `cves.yml` - All CVE templates
- `cloud.yml` - Cloud security templates
- `compliance.yml` - Compliance checking

## Special Features

### Workflow Templates
Multi-step scanning sequences that chain multiple checks together. Located in `workflows/` directory.

### Helper Files
- `helpers/wordlists/` - Common wordlists for fuzzing
- `helpers/payloads/` - Exploit payloads and test data

### OAST Integration
Templates can use out-of-band application security testing with `{{interactsh-url}}` for detecting blind vulnerabilities.

### Headless Browser Support
Templates in `headless/` directory support JavaScript execution and browser automation for SPA testing.

## Important Notes

- Templates failing honeypot validation are auto-labeled as false-positives
- All templates are cryptographically signed for authenticity
- WordPress plugin templates are automatically updated
- Statistics are auto-generated and should not be manually edited
- Use defensive security practices only - no malicious templates accepted

## Git and Commit Guidelines

### Commit Message Format
- Use clean, descriptive commit messages without automated signatures
- **NEVER** include "Co-Authored-By: Claude" or "Generated with Claude Code" in commit messages
- **NEVER** include emojis or AI-generated signatures in commits or PRs
- Focus on the actual changes and their purpose
- Reference issue numbers when applicable (e.g., "Fixes #12209")

### Pull Request Guidelines
- Use professional, human-like language in PR descriptions
- **NEVER** mention "Generated with Claude Code" or similar automated signatures
- Focus on technical details and the problem being solved
- Include clear summary of changes and references to related issues