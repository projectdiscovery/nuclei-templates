#!/usr/bin/env python3
"""
Mock Vulnerable Sitecore Instance for CVE-2025-34509 Testing
This simulates the vulnerable Sitecore XP behavior for template validation
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import uuid
import urllib.parse

class MockSitecoreHandler(BaseHTTPRequestHandler):
    def do_GET(self, *args, **kwargs):
        if self.path.startswith('/sitecore/api/sitecore/ItemService/'):
            self.handle_item_service()
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

    def do_POST(self, *args, **kwargs):
        if self.path == '/sitecore/api/ssc/auth/login':
            self.handle_login()
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

    def handle_login(self):
        """Handle ServicesAPI login with hardcoded credentials"""
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)

        try:
            data = json.loads(post_data.decode('utf-8'))

            # Check for hardcoded credentials
            if (data.get('domain') == 'sitecore' and
                data.get('username') == 'ServicesAPI' and
                data.get('password') == 'b'):

                # Generate session cookie
                session_id = str(uuid.uuid4()).replace('-', '')

                # Success response
                response = {
                    "success": True,
                    "token": f"mock_token_{session_id}",
                    "user": {
                        "userName": "ServicesAPI",
                        "domain": "sitecore"
                    }
                }

                self.send_response(200)
                self.send_header('Content-Type', 'application/json')
                self.send_header('Set-Cookie', f'ASP.NET_SessionId={session_id}; Path=/; HttpOnly')
                self.end_headers()
                self.wfile.write(json.dumps(response).encode('utf-8'))

                print(f"✅ Authentication successful for ServicesAPI")
            else:
                # Failed authentication
                response = {
                    "success": False,
                    "message": "Invalid credentials"
                }

                self.send_response(401)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps(response).encode('utf-8'))

                print(f"❌ Authentication failed: {data}")

        except Exception as e:
            self.send_response(400)
            self.end_headers()
            self.wfile.write(f'Bad Request: {str(e)}'.encode('utf-8'))

    def handle_item_service(self):
        """Handle ItemService API calls"""
        # Check for session cookie
        cookie_header = self.headers.get('Cookie', '')

        if 'ASP.NET_SessionId=' in cookie_header:
            # Simulate successful API response
            response = {
                "Items": [
                    {
                        "ID": "{110D559F-DEA5-42EA-9C1C-8A5DF7E70EF9}",
                        "Name": "Sitecore",
                        "TemplateName": "ItemService",
                        "Path": "/sitecore"
                    }
                ],
                "TotalCount": 1
            }

            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(response).encode('utf-8'))

            print(f"✅ ItemService API access successful")
        else:
            # No session cookie
            self.send_response(401)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({"error": "Unauthorized"}).encode('utf-8'))

            print(f"❌ ItemService API access denied - no session")

    def log_message(self, format, *args):
        """Override to reduce log noise"""
        pass

def run_server(port=8080):
    """Start the mock vulnerable Sitecore server"""
    server_address = ('', port)
    httpd = HTTPServer(server_address, MockSitecoreHandler)

    print(f"🚀 Mock Vulnerable Sitecore Server running on port {port}")
    print(f"📡 Test URL: http://localhost:{port}/sitecore")
    print(f"🔐 Hardcoded credentials: ServicesAPI / b")
    print(f"📋 Root item ID: {{110D559F-DEA5-42EA-9C1C-8A5DF7E70EF9}}")
    print(f"⏹️  Press Ctrl+C to stop")

    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print(f"\n🛑 Server stopped")
        httpd.shutdown()

if __name__ == '__main__':
    import sys
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8080
    run_server(port)
