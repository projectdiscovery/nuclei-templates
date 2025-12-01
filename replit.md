# Overview

This repository contains **Nuclei Templates** - a community-curated collection of security vulnerability detection templates for the Nuclei scanning engine. The templates are YAML-based definitions that enable automated security testing across multiple protocols (HTTP, DNS, TCP, SSL, etc.) to identify vulnerabilities, misconfigurations, exposed panels, and other security issues in web applications and infrastructure.

The repository serves as the core template library for ProjectDiscovery's Nuclei scanner, with over 9,000+ templates contributed by 800+ security researchers. It includes templates for CVE detection, common vulnerabilities, exposed services, cloud misconfigurations, and various attack vectors like XSS, SQL injection, RCE, LFI, and authentication bypasses.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Template Organization Structure

Templates are organized into a hierarchical directory structure based on vulnerability categories:

- **`http/`** - HTTP protocol templates (9,423 templates) for web application vulnerabilities
- **`cloud/`** - Cloud infrastructure templates (661 templates) including AWS, GCP, Azure, Alibaba
- **`file/`** - File-based vulnerability detection (445 templates)
- **`code/`** - Source code scanning templates (275 templates)
- **`network/`** - Network protocol templates (264 templates) including TCP-based checks
- **`dns/`** - DNS-specific templates (24 templates)
- **`ssl/`** - SSL/TLS configuration checks (38 templates)
- **`workflows/`** - Multi-step detection workflows (205 templates)
- **`javascript/`** - JavaScript-based templates (97 templates)
- **`dast/`** - Dynamic application security testing (241 templates)

## Template Structure and Schema

All templates follow a standardized YAML schema with key components:

**Required Fields:**
- `id` - Short identifier (max 3-4 words, e.g., "grafana-unauth-rce")
- `info.name` - Format: "Vendor Product Version - Vulnerability"
- `info.author` - Template creator and vulnerability discoverer (comma-separated)
- `info.severity` - Severity rating (info/low/medium/high/critical) aligned with CVSS
- `info.description` - Clear vulnerability explanation
- `info.reference` - POC/advisory links
- `info.classification` - CVE-ID, CWE-ID when applicable

**Protocol Handlers:**
- HTTP requests with matchers/extractors for response validation
- TCP/Network raw protocol interactions
- DNS queries for infrastructure enumeration
- File operations for local checks

## Quality Control and Validation

**Contribution Pipeline:**
1. Templates submitted via pull requests must include complete POCs (not version-based detection only)
2. Validation requires testable vulnerable environment (Docker setup or accessible instance)
3. Debug output (`nuclei -debug`) required for acceptance
4. Strong matchers to prevent false positives

**Metadata Enhancement:**
- Automated tagging system (vuln, cve, panel, xss, etc.)
- KEV (Known Exploited Vulnerabilities) tracking for CISA and VulnCheck catalogs
- EPSS (Exploit Prediction Scoring System) integration
- Search query metadata (Shodan, Fofa, Google dorks)

## Automation and CI/CD

**GitHub Actions Workflows:**
- Automated template assignment to reviewers
- KEV and vKEV tag updates from external feeds
- EPSS score synchronization
- Template statistics generation
- WordPress plugin template updates
- Checksum validation for integrity

**Scripts (`.github/scripts/`):**
- `assign_tasks.py` - Auto-assigns issues/PRs to reviewers
- `update-kev.py` - Syncs CISA KEV and VulnCheck KEV tags
- `update-epss.py` - Updates EPSS scores from FIRST API
- `count-kev-stats.py` - Generates KEV coverage statistics
- `update-readme.py` - Refreshes README with current metrics

## Helper Resources

**Payloads (`helpers/payloads/`):**
- Command injection patterns
- XSS payloads
- Request header fuzzing lists
- Protocol-specific test data

**Wordlists (`helpers/wordlists/`):**
- Common paths (Adminer, Grafana plugins, PrestaShop modules)
- Database credentials (MySQL users/passwords)
- SSH authentication lists
- HTTP parameters and headers
- User enumeration lists

## Configuration Profiles

Pre-configured scanning profiles in `profiles/`:
- `recommended.yml` - General purpose optimized configuration
- `privilege-escalation.yml` - Local privilege escalation checks
- `osint.yml` - Open source intelligence gathering
- Bug bounty, compliance, and custom scanning profiles

## Bounty Program

Managed template bounty system with rewards ($50-$250):
- Issues tagged with 💎 **Bounty** label
- `/attempt` command to claim issues (max 3 simultaneous)
- `/claim` command in PR to request reward
- 2-month completion timeline
- Validation required with testable instances sent to templates@projectdiscovery.io

# External Dependencies

## Core Dependencies

**Nuclei Scanner Engine:**
- The templates are consumed by the Nuclei scanning tool (github.com/projectdiscovery/nuclei)
- Templates are protocol-agnostic YAML definitions without code execution dependencies
- Scanner handles HTTP, DNS, TCP, SSL protocol implementations

## External Data Sources

**Vulnerability Catalogs:**
- CISA KEV (Known Exploited Vulnerabilities) - cisa.gov/known-exploited-vulnerabilities-catalog
- VulnCheck KEV - vulncheck.com/kev (requires API key via `VULNCHECK_API_KEY`)
- FIRST EPSS (Exploit Prediction Scoring System) - api.first.org/data/v1/epss
- NVD (National Vulnerability Database) for CVE metadata

**Community Resources:**
- WordPress.org plugins repository (for WP plugin templates)
- Shodan/Fofa/Censys for search query validation
- GitHub for template contributions and issue tracking

## Development Tools

**Python Dependencies (CI/CD scripts):**
- `requests` - HTTP client for API calls
- `beautifulsoup4` - HTML parsing for WordPress plugin extraction
- `pyyaml` - YAML template parsing
- Standard library: `json`, `re`, `pathlib`

**No Runtime Database:**
- Templates are static YAML files
- Statistics stored in JSON/Markdown files (version controlled)
- No persistent data store required

**Docker for Testing:**
- Optional Docker/docker-compose for vulnerable environment testing
- Not required for template execution (Nuclei scanner handles runtime)