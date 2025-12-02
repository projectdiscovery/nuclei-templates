#!/bin/bash
echo "==================================="
echo "CVE-2020-14756 Nuclei Templates"
echo "==================================="
echo ""
echo "Network Template:"
echo "-----------------"
if [ -f "network/cves/2020/CVE-2020-14756.yaml" ]; then
    echo "Location: network/cves/2020/CVE-2020-14756.yaml"
    echo "Status: Ready"
    head -35 network/cves/2020/CVE-2020-14756.yaml
else
    echo "Status: NOT FOUND"
fi
echo ""
echo "JavaScript Template:"
echo "--------------------"
if [ -f "javascript/cves/2020/CVE-2020-14756.yaml" ]; then
    echo "Location: javascript/cves/2020/CVE-2020-14756.yaml"
    echo "Status: Ready"
    head -35 javascript/cves/2020/CVE-2020-14756.yaml
else
    echo "Status: NOT FOUND"
fi
echo ""
echo "==================================="
echo "Templates are ready for PR submission to:"
echo "https://github.com/projectdiscovery/nuclei-templates"
echo "==================================="
