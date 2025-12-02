# Nuclei Templates Repository

This is a fork/clone of the projectdiscovery/nuclei-templates repository for contributing CVE-2020-14756 templates.

## CVE-2020-14756 Templates

Two templates have been created for Oracle Coherence RCE vulnerability:

### 1. Network Template
- **Location:** `network/cves/2020/CVE-2020-14756.yaml`
- **Protocol:** TCP/T3
- **Detection:** OOB verification via interactsh

### 2. JavaScript Template  
- **Location:** `javascript/cves/2020/CVE-2020-14756.yaml`
- **Protocol:** T3 via nuclei/net module
- **Detection:** OOB verification via interactsh

## Key Features
- Complete serialized gadget chain (AttributeHolder → TopNAggregator → MvelExtractor)
- OOB verification eliminates false positives
- Supports both Linux and Windows targets (JS template)

## Repository Structure
This follows the standard nuclei-templates structure:
- `network/` - Network protocol templates
- `javascript/` - JavaScript-based templates
- `http/` - HTTP templates
- `dns/` - DNS templates
- etc.

## For PR Submission
The templates are ready in their correct locations for submission to:
https://github.com/projectdiscovery/nuclei-templates
