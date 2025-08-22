from flask import Flask, request, jsonify

app = Flask(__name__)

# This is the main endpoint our template is targeting
@app.route('/api/v2.0/system/status.systemstatus', methods=['GET'])
def system_status():
    # Print the received cookie for debugging
    auth_cookie = request.headers.get('Cookie', '')
    print(f"[DEBUG] Received Cookie: {auth_cookie}")

    # THE CORE VULNERABILITY LOGIC
    # Check if the malicious "Era=9" parameter is present in the cookie
    if 'Era=9' in auth_cookie:
        print("[+] VULNERABILITY TRIGGERED! Authentication Bypass successful via Era=9.")
        # If it is, behave like a vulnerable device and return sensitive system info
        response_data = {
            "results": {
                "hostName": "mock-fortiweb-vm",
                "operationMode": "Reverse Proxy",
                "firmwareVersion": "7.4.1",
                "administrativeDomain": "Enabled"
            }
        }
        return jsonify(response_data), 200, {'Content-Type': 'application/json'}
    else:
        # If the Era parameter is normal, deny access
        print("[-] Valid Era value not found. Access denied.")
        return jsonify({"error": "Unauthorized"}), 401

# Optional: Add a root endpoint to simulate the admin UI
@app.route('/')
def admin_ui():
    auth_cookie = request.headers.get('Cookie', '')
    if 'Era=9' in auth_cookie:
        return "<html><title>FortiWeb Admin</title><body><h1>Dashboard</h1><p>System Information</p><a href='#'>Logout</a></body></html>", 200
    else:
        return "<html><body>Please log in.</body></html>", 200

if __name__ == '__main__':
    print("Starting mock vulnerable FortiWeb server on http://0.0.0.0:8080")
    app.run(host='0.0.0.0', port=8080, debug=True)
