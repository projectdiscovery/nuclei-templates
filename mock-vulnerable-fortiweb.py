#!/usr/bin/env python3
"""
Mock Vulnerable FortiWeb Server for CVE-2025-52970 Testing
Simulates the broken access control vulnerability in FortiWeb
"""

from flask import Flask, request, jsonify, make_response
import json

app = Flask(__name__)

# Mock FortiWeb configuration
FORTIWEB_VERSION = "7.6.3"
VULNERABLE_VERSIONS = ["7.6.3", "7.4.7", "7.2.10", "7.0.10"]

def is_vulnerable_version():
    """Check if the current version is vulnerable"""
    return FORTIWEB_VERSION in VULNERABLE_VERSIONS

def check_access_control_bypass():
    """
    Simulate the broken access control vulnerability
    In real FortiWeb, this would be caused by improper parameter handling
    that allows unauthenticated access to admin functions
    """
    # Check for specific headers that might indicate internal/admin access
    internal_headers = [
        'X-Forwarded-For',
        'X-Real-IP',
        'X-Client-IP',
        'X-Remote-IP',
        'X-Originating-IP',
        'X-Remote-Addr'
    ]

    # If any internal IP headers are present, simulate the bypass
    for header in internal_headers:
        if header in request.headers:
            ip_value = request.headers[header]
            # Simulate the vulnerability - internal IPs get admin access
            if ip_value in ['127.0.0.1', '::1', 'localhost']:
                return True

    return False

@app.route('/api/v2.0/system/status', methods=['GET'])
def system_status():
    """System status endpoint - should be accessible with bypass"""
    if not is_vulnerable_version():
        return jsonify({"error": "Not vulnerable version"}), 404

    if check_access_control_bypass():
        return jsonify({
            "FortiWeb": True,
            "system": {
                "status": "online",
                "version": FORTIWEB_VERSION,
                "uptime": "7d 12h 30m",
                "cpu_usage": "15%",
                "memory_usage": "45%"
            },
            "vulnerable": True
        })
    else:
        return jsonify({"error": "Access denied"}), 401

@app.route('/api/v2.0/system/admin', methods=['GET'])
def system_admin():
    """Admin endpoint - should be accessible with bypass"""
    if not is_vulnerable_version():
        return jsonify({"error": "Not vulnerable version"}), 404

    if check_access_control_bypass():
        return jsonify({
            "admin": {
                "users": [
                    {"name": "admin", "role": "super_admin", "status": "active"},
                    {"name": "user1", "role": "admin", "status": "active"}
                ],
                "privilege": "full_access",
                "permissions": ["read", "write", "delete", "config"]
            },
            "vulnerable": True
        })
    else:
        return jsonify({"error": "Access denied"}), 401

@app.route('/api/v2.0/system/config', methods=['GET'])
def system_config():
    """Config endpoint - should be accessible with bypass"""
    if not is_vulnerable_version():
        return jsonify({"error": "Not vulnerable version"}), 404

    if check_access_control_bypass():
        return jsonify({
            "config": {
                "setting": {
                    "admin_timeout": "30m",
                    "max_sessions": "1000",
                    "security_level": "high"
                },
                "parameter": {
                    "debug_mode": False,
                    "log_level": "info",
                    "backup_enabled": True
                },
                "vulnerable": True
            }
        })
    else:
        return jsonify({"error": "Access denied"}), 401

@app.route('/api/v2.0/system/info', methods=['GET'])
def system_info():
    """General system info - always accessible"""
    return jsonify({
        "FortiWeb": True,
        "version": FORTIWEB_VERSION,
        "model": "FortiWeb-3000D",
        "serial": "FWB3K0000000001"
    })

@app.route('/', methods=['GET'])
def index():
    """Main page"""
    return """
    <html>
    <head><title>FortiWeb Management Interface</title></head>
    <body>
        <h1>FortiWeb Management Interface</h1>
        <p>Version: {}</p>
        <p>This is a mock vulnerable FortiWeb server for CVE-2025-52970 testing</p>
        <p>Vulnerable to broken access control - admin privileges can be bypassed</p>
    </body>
    </html>
    """.format(FORTIWEB_VERSION)

if __name__ == '__main__':
    print("🚀 Mock Vulnerable FortiWeb Server running on port 8080")
    print("📡 Test URL: http://localhost:8080")
    print("🔓 Vulnerable to CVE-2025-52970 - Broken Access Control")
    print("💡 Use internal IP headers to trigger the vulnerability")
    print("🔧 Example: X-Forwarded-For: 127.0.0.1")
    app.run(host='0.0.0.0', port=8080, debug=False)
