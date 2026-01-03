#!/bin/bash
echo "==================================="
echo "CVE-2020-14756 Template Lint Check"
echo "==================================="
echo ""

# Check for trailing spaces
echo "Checking for trailing spaces..."
echo ""

JS_TRAILING=$(grep -n '\s$' javascript/cves/2020/CVE-2020-14756.yaml 2>/dev/null | wc -l)
NET_TRAILING=$(grep -n '\s$' network/cves/2020/CVE-2020-14756.yaml 2>/dev/null | wc -l)

echo "JavaScript Template:"
echo "  Location: javascript/cves/2020/CVE-2020-14756.yaml"
if [ "$JS_TRAILING" -eq 0 ]; then
    echo "  Trailing spaces: PASS (none found)"
else
    echo "  Trailing spaces: FAIL ($JS_TRAILING lines with trailing spaces)"
    grep -n '\s$' javascript/cves/2020/CVE-2020-14756.yaml
fi
echo ""

echo "Network Template:"
echo "  Location: network/cves/2020/CVE-2020-14756.yaml"
if [ "$NET_TRAILING" -eq 0 ]; then
    echo "  Trailing spaces: PASS (none found)"
else
    echo "  Trailing spaces: FAIL ($NET_TRAILING lines with trailing spaces)"
    grep -n '\s$' network/cves/2020/CVE-2020-14756.yaml
fi
echo ""

# Check YAML syntax
echo "Checking YAML syntax..."
echo ""

if command -v python3 &> /dev/null; then
    python3 -c "import yaml; yaml.safe_load(open('javascript/cves/2020/CVE-2020-14756.yaml'))" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "  JavaScript Template YAML: PASS"
    else
        echo "  JavaScript Template YAML: FAIL"
    fi
    
    python3 -c "import yaml; yaml.safe_load(open('network/cves/2020/CVE-2020-14756.yaml'))" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "  Network Template YAML: PASS"
    else
        echo "  Network Template YAML: FAIL"
    fi
else
    echo "  Python not available for YAML validation"
fi

echo ""
echo "==================================="
if [ "$JS_TRAILING" -eq 0 ] && [ "$NET_TRAILING" -eq 0 ]; then
    echo "OVERALL: PASS - Templates are lint-clean"
else
    echo "OVERALL: FAIL - Fix trailing spaces"
fi
echo "==================================="
