# Nuclei Template Review Guide
**Best Practices for Template Quality and Security**

---

## Quick Reference

### **Review Priority Areas**
1. **Quality & Consistency** - Formatting, syntax, field standardization
2. **Metadata Enhancement** - Search queries, classifications, documentation  
3. **Tags Optimization** - Category alignment, searchability
4. **Classification** - CVE/CWE alignment, severity scoring
5. **Matcher Refinement** - Detection logic, false positive prevention

### **Common Areas for Improvement**
- **Community Contributions:** Often need metadata and classification enhancements
- **Template Reviews:** Focus on technical accuracy and matcher quality

---

## Pre-Review Checklist

### **Template Completeness Verification**
```yaml
Required Fields Checklist:
- [ ] id: Short, descriptive (max 3-4 words)
- [ ] name: Format "Vendor Product Version - Vulnerability"  
- [ ] author: Template creator, vulnerability discoverer (comma-separated)
- [ ] severity: info/low/medium/high/critical (CVSS aligned)
- [ ] description: Clear vulnerability explanation
- [ ] reference: Valid POC/advisory links
- [ ] classification: CVE-ID, CWE-ID if applicable
```

### **Author Attribution Best Practices**
```yaml
# GOOD - Include both template author and vulnerability discoverer in author field
info:
  author: template-author-github-username,original-discoverer-handle
  description: SQL injection vulnerability in Example App v2.1.0 allows remote code execution.
  reference:
    - https://github.com/security_researcher/advisories/example-app-sqli
    - https://nvd.nist.gov/vuln/detail/CVE-2024-1234

# EXAMPLES of proper author attribution
info:
  author: princechaddha,0xd0ff9  # Template by princechaddha, vulnerability found by 0xd0ff9

info:
  author: dhiyaneshDk,researcher_name  # Template by dhiyaneshDk, discovered by researcher_name

info:
  author: geeknik,@security_handle  # Template by geeknik, found by @security_handle
```

### **Metadata Quality Check**
```yaml
Enhanced Metadata:
- [ ] verified: true (if tested with debug data)
- [ ] shodan-query: Search dork for asset discovery
- [ ] fofa-query: Alternative search engine query
- [ ] tags: Appropriate categories (cve, rce, lfi, etc.)

Note: max-request is auto-generated and doesn't need manual input
```

---

## Matcher Quality Framework

### **🚫 Weak Matchers (High False Positive Risk)**

#### **Generic Response Patterns - AVOID**
```yaml
# BAD - Matches any admin interface globally
matchers:
  - type: word
    words:
      - "admin"
      - "login"
      - "dashboard"
    part: body
```
**Problem:** These terms appear on millions of legitimate systems

#### **Version-Only Detection - AVOID**
```yaml  
# BAD - Matches all versions, not just vulnerable ones
matchers:
  - type: regex
    regex:
      - 'WordPress [0-9]+\.[0-9]+'
    part: body
```
**Problem:** Doesn't verify actual vulnerability presence

### **✅ Strong Vulnerability-Specific Matchers**

#### **Multi-Layer Verification Strategy**
```yaml
# GOOD - Combines application + version + vulnerability proof
matchers-condition: and
matchers:
  - type: word                    # Layer 1: Identify specific application
    words:
      - "Grafana Dashboard"
      - "grafana.com/login"
    part: body
    
  - type: regex                   # Layer 2: Confirm vulnerable version range
    regex:
      - 'version.*[6-8]\.[0-5]\.[0-9]'
    part: body
    
  - type: word                    # Layer 3: Verify exploit success
    words:
      - "unauthorized_data_access"
      - "/api/snapshots/{{randstr}}"
    part: body
    condition: or
```

#### **CVE-Specific Template Example**
```yaml
# CVE-2024-1234 - Specific Application RCE
matchers-condition: and  
matchers:
  - type: word
    words:
      - "VulnApp Management Console v2.1.0"    # Exact vulnerable version
      - "Build 2024.03.15"                     # Specific build identifier
    part: body
    
  - type: status                              # Expected response code
    status:
      - 200
      
  - type: word                                # Exploitation proof
    words:  
      - "RCE_EXECUTION_SUCCESS_{{randstr}}"    # Unique payload response
      - "nuclei_test_command_output"           # Command execution indicator
    part: body
    condition: or
```

#### **Panel Detection - Unique Signatures**
```yaml  
# FortiGate Panel - Multiple unique identifiers
matchers-condition: and
matchers:
  - type: word                    
    words:
      - "<title>FortiGate - Login</title>"     # Specific page title
      - "Fortinet, Inc."                       # Vendor attribution  
      - "FortiOS Version:"                     # Version indicator
    part: body
    condition: or
    
  - type: regex
    regex:
      - '/images/fortinet_logo_[0-9]+\.png'    # Unique asset pattern
      - 'build_[0-9]{4}_[0-9]{6}'              # Build number format specific to FortiGate
    part: body
    condition: or
```

---

## False Positive Prevention

### **High-Risk Scenarios to Test**
Before approval, verify matchers DON'T trigger on:

1. **Related Non-Vulnerable Systems**
   - Same vendor, different products
   - Same application, patched versions
   - Similar interfaces, different implementations

2. **Generic Web Applications**
   - Common frameworks (WordPress, Django, Rails)
   - Standard admin panels (cPanel, Plesk)
   - Default server pages (Apache, Nginx)

3. **Honeypot/Security Tools**
   - Intentionally deceptive responses
   - Security scanners mimicking vulnerabilities
   - Research environments with fake responses

### **Matcher Testing Checklist**
```yaml
Required Validation Steps:
- [ ] Test against 3+ non-vulnerable similar applications
- [ ] Verify unique response elements are truly unique
- [ ] Confirm payload/exploitation indicators are specific
- [ ] Check matcher doesn't trigger on default installations
- [ ] Validate version detection accuracy (if applicable)
```

---

## Review Categories by Impact

### **1. Metadata Enhancement**
**Most Common Missing Elements:**
- `shodan-query` for asset discovery
- `fofa-query` for alternative search engines
- `classification` fields for vulnerability tracking

**Review Actions:**
```yaml
# Add asset discovery capabilities
metadata:
  verified: true
  shodan-query: 'http.title:"Grafana" http.component:"Grafana"'
  fofa-query: 'title="Grafana" && body="grafana"'

# Ensure proper classification
classification:
  cvss-metrics: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:C/C:H/I:H/A:H
  cvss-score: 9.8
  cve-id: CVE-2024-1234
  cwe-id: CWE-89
```

### **2. Matcher Quality**
**Common Issues:**
- Overly broad detection patterns
- Missing context verification
- Weak exploitation proof

**Review Standards:**
```yaml
# Require multi-layer validation
matchers-condition: and  # Force all conditions to match
matchers:
  - type: word            # Application identification
  - type: status          # Response validation  
  - type: word            # Vulnerability proof
    condition: or         # Allow multiple proof methods
```

### **3. Severity Assessment**
**CVSS Alignment Requirements:**
- **Critical (9.0-10.0):** Remote code execution, complete system compromise
- **High (7.0-8.9):** Significant data breach, privilege escalation
- **Medium (4.0-6.9):** Information disclosure, limited access
- **Low (0.1-3.9):** Minor information leakage, requires authentication

### **4. Reference Validation**
**Required Reference Quality:**
- Original vulnerability advisory (CVE, vendor security bulletin)
- Technical analysis or proof of concept
- NIST NVD entry (if available)
- Public disclosure timeline

---

## Reviewer Action Templates

### **For Metadata Improvements**
```
Please enhance the template metadata:
- Add `shodan-query` for asset discovery: `[suggested query]`
- Include `fofa-query` for alternative search: `[suggested query]`  
- Verify `classification` fields are complete
- Ensure `verified: true` with debug data if tested
```

### **For Weak Matchers**
```
The current matchers may produce false positives. Please:
- Add application-specific identifiers (exact titles, unique elements)
- Include version validation for vulnerable ranges only
- Verify exploitation proof rather than just version detection
- Test against [specific non-vulnerable scenarios]
```

### **For Severity Issues**
```
Severity should be [X] based on CVSS impact:
- Attack Vector: [Network/Adjacent/Local/Physical]
- Impact: [Complete/Partial/None] for CIA triad
- Required Privileges: [None/Low/High]
- Reference CVSS calculator: [link]
```

### **For Missing References**
```
Please add comprehensive references:
- Original vulnerability disclosure/advisory
- Technical analysis or exploitation details  
- Official vendor security bulletin (if available)
- NIST NVD entry: https://nvd.nist.gov/vuln/detail/CVE-XXXX-XXXX
```

### **For Missing Vulnerability Discoverer Attribution**
```
Please credit the original vulnerability discoverer in the author field:
- Update author field: "template-author,vulnerability-discoverer" (comma-separated)
- Example: "princechaddha,0xd0ff9" or "dhiyaneshDk,researcher_name"
- Include original disclosure/research reference
- Ensure proper credit for both template creator AND vulnerability researcher
```

---

## Quality Standards & Goals

### **Template Accuracy Goals**
- **False Positive Rate:** <2% (verified through community feedback)
- **Detection Coverage:** 95%+ for vulnerable instances
- **Metadata Completeness:** 100% required fields, 80%+ enhanced fields

### **Review Consistency Standards**
- **Response Time:** Initial review within 48 hours
- **Feedback Quality:** Specific, actionable guidance with examples
- **Approval Criteria:** All quality checks pass, tested when possible

### **Community Impact**
- **Contribution Quality:** Monitor improvement patterns in repeat contributors
- **Review Effectiveness:** Track issues caught in review vs. post-merge
- **Knowledge Transfer:** Successful elevation of community template quality

---

## Advanced Review Techniques

### **Template Testing Environment Setup**
```bash
# Recommended testing workflow
nuclei -t new-template.yaml -target vulnerable-lab.com -debug
nuclei -t new-template.yaml -target patched-system.com -debug
nuclei -t new-template.yaml -list target-variety.txt -silent
```

### **Vulnerability Context Research**
1. **CVE Database Cross-Reference:** Verify vulnerability details match template scope
2. **Vendor Advisory Review:** Confirm affected versions and exploitation requirements  
3. **Community Disclosure Analysis:** Check for additional context or limitations
4. **Exploit Database Validation:** Compare detection approach with known exploits

### **Review Documentation Standards**
- Document testing methodology in PR comments
- Provide specific improvement suggestions with examples
- Reference similar high-quality templates as comparison
- Explain security rationale for matcher requirements

---

## Practical Examples by Vulnerability Type

### **SQL Injection Templates**
```yaml
# WRONG - Generic database errors (match many systems)
matchers:
  - type: word
    words:
      - "mysql_fetch"
      - "SQL syntax"

# RIGHT - Specific to vulnerability context
matchers:
  - type: word
    words:
      - "mysql_fetch_array(): supplied argument"    # Specific error pattern
      - "WHERE id='1' OR '1'='1'"                   # Injection payload context
      - "users table does not exist"                # Application-specific error
    part: body
    condition: and
```

### **RCE Templates**
```yaml
# WRONG - Generic command output
matchers:
  - type: word
    words:
      - "root"
      - "/bin/bash"

# RIGHT - Command execution in vulnerability context
matchers:
  - type: regex
    regex:
      - 'uid=0\(root\) gid=0\(root\) groups=0\(root\)'  # Complete whoami output
    part: body
  - type: word
    words:
      - "nuclei_rce_test_{{rand_base(6)}}"               # Unique payload identifier
    part: body
```

### **File Upload Vulnerabilities**
```yaml
# WRONG - Generic file upload success
matchers:
  - type: word
    words:
      - "uploaded successfully"
      - "file saved"

# RIGHT - Vulnerability-specific upload validation
matchers:
  - type: word
    words:
      - "nuclei_test_{{randstr}}.php uploaded to /var/www/uploads/"  # Specific path + file
      - "File permissions: 644"                                      # System response
      - "<?php echo 'nuclei_upload_test'; ?>"                       # File content verification
    part: body
    condition: and
```

---

## Multi-Layer Matcher Strategy

```yaml
# Use multiple verification layers
matchers-condition: and
matchers:
  - type: word           # Layer 1: Identify the application
    words:
      - "VulnApp Management Console"
    part: body
    
  - type: regex          # Layer 2: Confirm vulnerable version
    regex:
      - 'Version: [1-2]\.[0-5]\.[0-9]'
    part: body
    
  - type: word           # Layer 3: Verify vulnerability exists
    words:
      - "debug_info_exposed"
      - "configuration_leak"
    part: body
    condition: or
```

---

*This guide provides comprehensive standards for creating and reviewing high-quality nuclei templates that are accurate, specific, and minimize false positives.*