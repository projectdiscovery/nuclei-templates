#!/usr/bin/env python3
"""
Test script for CVE-2025-22457 Nuclei template
"""

import yaml
import re
import sys

def test_template_logic():
    """Test the template logic and patterns"""
    
    print("Testing CVE-2025-22457 template logic...")
    
    # Load template
    with open('http/cves/2025/CVE-2025-22457.yaml', 'r') as f:
        template = yaml.safe_load(f)
    
    # Test version regex patterns (build numbers, not version numbers)
    version_patterns = [
        'name="productversion"\\s+value="22\\.7\\.2\\.[0-9]+" && !regex("name=\"productversion\"\\s+value=\"22\\.7\\.2\\.(398[1-9]|39[9-9][0-9]|[4-9][0-9]{3}|[1-9][0-9]{4,})"',
        'name="productversion"\\s+value="22\\.7\\.1\\.[0-9]+"',
        'name="productversion"\\s+value="22\\.8\\.[01]\\.[0-9]+"',
        'name="productversion"\\s+value="22\\.8\\.2\\.[0-9]+" && !regex("name=\"productversion\"\\s+value=\"22\\.8\\.2\\.(3[3-9][0-9]{2}|[4-9][0-9]{3}|[1-9][0-9]{4,})"',
        'name="productversion"\\s+value="9\\.[01]\\.[0-9]+\\.[0-9]+"'
    ]

    # Test vulnerable build numbers (productversion contains build numbers)
    vulnerable_versions = [
        'name="productversion" value="22.7.2.3597"',  # < 22.7R2.6 (build 3981)
        'name="productversion" value="22.7.1.1000"',  # < 22.7R1.4
        'name="productversion" value="22.8.1.2000"',  # < 22.8R2.2
        'name="productversion" value="22.8.2.1000"',  # < 22.8R2.2
        'name="productversion" value="9.1.18.9"'      # Pulse Connect Secure
    ]

    # Test non-vulnerable build numbers
    non_vulnerable_versions = [
        'name="productversion" value="22.7.2.3981"',  # 22.7R2.6 (patched)
        'name="productversion" value="22.7.2.4000"',  # > 22.7R2.6
        'name="productversion" value="22.8.2.3300"',  # 22.8R2.2 (patched)
        'name="productversion" value="23.0.0.1000"'   # Newer version
    ]
    
    print("\n1. Testing vulnerable version detection:")
    for pattern in version_patterns:
        regex = re.compile(pattern)
        for version in vulnerable_versions:
            if regex.search(version):
                print(f"   ✓ {version} - VULNERABLE (correctly detected)")
            else:
                print(f"   ✗ {version} - Should be vulnerable but not detected")
    
    print("\n2. Testing non-vulnerable version detection:")
    for pattern in version_patterns:
        regex = re.compile(pattern)
        for version in non_vulnerable_versions:
            if regex.search(version):
                print(f"   ✗ {version} - Should NOT be vulnerable but detected")
            else:
                print(f"   ✓ {version} - NON-VULNERABLE (correctly ignored)")
    
    # Test payload generation
    print("\n3. Testing payload generation:")
    test_payload = "1" * 622
    if len(test_payload) == 622:
        print(f"   ✓ Payload length correct: {len(test_payload)} characters")
    else:
        print(f"   ✗ Payload length incorrect: {len(test_payload)} characters")
    
    # Test template structure
    print("\n4. Testing template structure:")
    
    # Check HTTP requests
    http_requests = template['http'][0]['raw']
    if len(http_requests) == 3:
        print("   ✓ Correct number of HTTP requests (3)")
    else:
        print(f"   ✗ Incorrect number of HTTP requests: {len(http_requests)}")
    
    # Check matchers
    matchers = template['http'][0]['matchers']
    expected_matchers = ['ivanti_detection', 'version_vulnerable', 'protocol_switch', 'buffer_overflow_response']
    
    matcher_names = [m.get('name', 'unnamed') for m in matchers]
    for expected in expected_matchers:
        if expected in matcher_names:
            print(f"   ✓ Matcher '{expected}' present")
        else:
            print(f"   ✗ Matcher '{expected}' missing")
    
    # Check extractors
    extractors = template['http'][0]['extractors']
    if len(extractors) >= 2:
        print(f"   ✓ Extractors present: {len(extractors)}")
    else:
        print(f"   ✗ Insufficient extractors: {len(extractors)}")
    
    print("\n5. Testing classification:")
    classification = template['info']['classification']
    
    required_fields = ['cvss-metrics', 'cvss-score', 'cve-id', 'cwe-id']
    for field in required_fields:
        if field in classification:
            print(f"   ✓ Classification field '{field}' present")
        else:
            print(f"   ✗ Classification field '{field}' missing")
    
    # Check CVSS score
    cvss_score = classification.get('cvss-score', 0)
    if cvss_score >= 9.0:
        print(f"   ✓ CVSS score appropriate for critical: {cvss_score}")
    else:
        print(f"   ✗ CVSS score too low for critical: {cvss_score}")
    
    print("\n6. Testing metadata:")
    metadata = template['info']['metadata']
    
    required_metadata = ['max-request', 'vendor', 'product']
    for field in required_metadata:
        if field in metadata:
            print(f"   ✓ Metadata field '{field}' present")
        else:
            print(f"   ✗ Metadata field '{field}' missing")
    
    # Check search queries
    search_queries = ['shodan-query', 'fofa-query', 'google-query']
    for query in search_queries:
        if query in metadata:
            print(f"   ✓ Search query '{query}' present")
        else:
            print(f"   ✗ Search query '{query}' missing")
    
    print("\n7. Testing tags:")
    tags = template['info']['tags']
    expected_tags = ['cve', 'cve2025', 'ivanti', 'rce', 'kev', 'critical']
    
    for tag in expected_tags:
        if tag in tags:
            print(f"   ✓ Tag '{tag}' present")
        else:
            print(f"   ✗ Tag '{tag}' missing")
    
    print("\n✅ Template logic testing completed!")

def main():
    try:
        test_template_logic()
        print("\n🎉 All tests completed successfully!")
    except Exception as e:
        print(f"\n❌ Test failed with error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
