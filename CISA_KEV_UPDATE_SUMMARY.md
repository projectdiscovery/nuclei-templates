# CISA KEV Update Summary

## Task Completed ✅

Successfully fetched the latest CISA Known Exploited Vulnerabilities (KEV) catalog and updated Nuclei templates with the 'kev' tag.

## What Was Done

1. **Fetched CISA KEV Data**: Downloaded the latest KEV catalog from `https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json`
   - Total CVEs in KEV catalog: **1,373**
   - Publication date: 2025-07-01T17:00:59.134Z

2. **Analyzed Repository**: Scanned all 3,183 CVE YAML files in the repository
   - Found 330 CVEs from KEV catalog that have corresponding templates
   - 313 already had the 'kev' tag
   - **17 needed the 'kev' tag added**

3. **Updated Templates**: Successfully added 'kev' tag to 17 CVE templates

## Updated CVE Templates

| CVE ID | File Path | Vulnerability Type |
|--------|-----------|-------------------|
| CVE-2014-0160 | code/cves/2014/CVE-2014-0160.yaml | OpenSSL Heartbleed |
| CVE-2024-12356 | code/cves/2024/CVE-2024-12356.yaml | - |
| CVE-2025-32433 | code/cves/2025/CVE-2025-32433.yaml | - |
| CVE-2021-45046 | dast/cves/2021/CVE-2021-45046.yaml | Log4j |
| CVE-2016-8735 | http/cves/2016/CVE-2016-8735.yaml | Apache Tomcat RCE |
| CVE-2017-12637 | http/cves/2017/CVE-2017-12637.yaml | - |
| CVE-2017-3506 | http/cves/2017/CVE-2017-3506.yaml | - |
| CVE-2019-16278 | http/cves/2019/CVE-2019-16278.yaml | - |
| CVE-2021-32030 | http/cves/2021/CVE-2021-32030.yaml | - |
| CVE-2023-1389 | http/cves/2023/CVE-2023-1389.yaml | - |
| CVE-2023-20198 | http/cves/2023/CVE-2023-20198.yaml | - |
| CVE-2024-20439 | http/cves/2024/CVE-2024-20439.yaml | - |
| CVE-2024-41713 | http/cves/2024/CVE-2024-41713.yaml | - |
| CVE-2024-48248 | http/cves/2024/CVE-2024-48248.yaml | - |
| CVE-2024-56145 | http/cves/2024/CVE-2024-56145.yaml | - |
| CVE-2025-24016 | http/cves/2025/CVE-2025-24016.yaml | - |
| CVE-2024-40711 | passive/cves/2024/CVE-2024-40711.yaml | - |

## Git Changes

- **Branch**: `cursor/update-cves-with-cisa-kev-tags-75ae`
- **Commit**: `05c470b4f` - "Add kev tag to CVEs from CISA Known Exploited Vulnerabilities catalog"
- **Files Modified**: 17 CVE templates
- **Changes**: Added 'kev' tag to each template's tags field

## Pull Request

**🔗 Create PR**: https://github.com/projectdiscovery/nuclei-templates/pull/new/cursor/update-cves-with-cisa-kev-tags-75ae

The branch has been pushed to the repository. Use the link above to create the pull request.

## Impact

This update helps security teams:
- **Prioritize** remediation efforts on actively exploited vulnerabilities
- **Filter** Nuclei scans to focus on KEV vulnerabilities
- **Identify** high-risk vulnerabilities in their environment
- **Stay current** with CISA's known exploited vulnerabilities

## Quality Assurance

✅ All 17 templates successfully updated  
✅ No existing 'kev' tags were removed  
✅ Only CVEs actually present in CISA KEV catalog were tagged  
✅ Changes verified by manual inspection  
✅ Temporary files cleaned up  

## Repository Stats

- **Total CVE templates**: 3,183
- **KEV CVEs with templates**: 330 (24% of CISA KEV catalog)
- **Templates with kev tag**: 330 (313 existing + 17 newly added)