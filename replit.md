# Nuclei Templates Browser

## Overview
This is a web-based browser for the Nuclei Templates repository - a community-curated collection of security scanning templates for the Nuclei engine. The original repository contains thousands of YAML templates for finding security vulnerabilities in applications.

## Purpose
The Nuclei Templates Browser provides an easy-to-use web interface to:
- Browse through security scanning templates
- Search templates by name, description, CVE, or tags
- Filter templates by severity level
- View detailed information about each template
- Access statistics about the template collection

## Current State
The application is fully functional and running on Replit with:
- **Frontend**: Modern, responsive web interface with search and filtering capabilities
- **Backend**: Node.js Express server serving the template data
- **Data**: 11,997+ security templates across 873 directories
- **Coverage**: 3,669 CVEs, 453 KEV (Known Exploited Vulnerabilities) templates

## Recent Changes (November 30, 2025)
- Created Express.js web server to serve template browser
- Built responsive frontend with search and filter functionality
- Configured workflow to run on port 5000
- Set up deployment configuration for autoscale deployment
- Fixed bug handling template tags (string vs array normalization)

## Project Architecture

### Technology Stack
- **Runtime**: Node.js 20
- **Backend**: Express.js
- **Template Parsing**: js-yaml
- **Frontend**: Vanilla JavaScript, HTML5, CSS3

### File Structure
```
/
├── server.js              # Express server with API endpoints
├── package.json           # Node.js dependencies
├── public/                # Frontend files
│   ├── index.html        # Main HTML interface
│   ├── style.css         # Responsive styles
│   └── app.js            # Frontend JavaScript
├── http/                  # HTTP templates directory
├── network/              # Network templates directory
├── dns/                  # DNS templates directory
├── cloud/                # Cloud templates directory
├── javascript/           # JavaScript templates directory
└── ... (other template directories)
```

### API Endpoints
- `GET /api/stats` - Returns template statistics from TEMPLATES-STATS.json
- `GET /api/templates?search=X&severity=Y&limit=Z` - Search and filter templates
- `GET /api/template/:path` - Get specific template details

### Key Features
1. **Search**: Full-text search across template names, descriptions, IDs, and tags
2. **Filter**: Filter by severity (critical, high, medium, low, info)
3. **Limit Control**: Adjust number of results (10-500)
4. **Statistics**: View collection statistics (CVEs, KEV templates, etc.)
5. **Template Details**: View author, severity, tags, CVE IDs, and full file paths

## Deployment
The application is configured for Replit deployment using the autoscale deployment target, which is ideal for this stateless web application.

## User Preferences
No specific user preferences recorded yet.

## Development Notes
- Port 5000 is used for the web server (required for Replit webview)
- Server binds to 0.0.0.0 to allow external connections
- Template parsing handles both string and array tag formats
- Templates are loaded on-demand to improve performance
