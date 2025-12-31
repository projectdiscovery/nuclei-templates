from flask import Flask, request, Response
import re
import requests
import base64

app = Flask(__name__)

# Simulated NTLM states
# In a real attack, we'd relay these. For a mock, we just facilitate the handshake.

@app.route('/')
def home():
    return "NTLM Mock Exchange Server Running"

@app.route('/ews/exchange.asmx', methods=['POST', 'GET'])
def ews():
    try:
        auth_header = request.headers.get('Authorization')

        # Step 1: No Auth Header -> Return 401 with NTLM
        if not auth_header:
            resp = Response("Unauthorized", 401)
            resp.headers['WWW-Authenticate'] = 'NTLM'
            resp.headers['Server'] = 'Microsoft-IIS/8.5'
            resp.headers['X-Powered-By'] = 'ASP.NET'
            return resp

        # Step 2: Client sends NTLM Type 1 -> Return Type 2 (Challenge)
        if auth_header.startswith('NTLM '):
            try:
                ntlm_blob = base64.b64decode(auth_header.split(' ')[1])
                if b'NTLMSSP' in ntlm_blob and len(ntlm_blob) < 100:
                    type2_blob = "TlRMTVNTUAACAAAADAAMADAAAAABAoEAASNFZ4mrue8AAAAAAAAAAGIAYgA8AAAABgMbDlQARQBTAFQAAgAMAFQARQBTAFQAAQAQAFQARQBTAFQALQBQAEMABAAQAFQARQBTAFQALQBQAEMAAwAQAFQARQBTAFQALQBQAEMABwAIADU+OaxIOdYBBgAEAAIAAAAIADAAMAAAAAAAAAEBAAAAAA=="
                    resp = Response("Unauthorized", 401)
                    resp.headers['WWW-Authenticate'] = f'NTLM {type2_blob}'
                    return resp
            except Exception as e:
                print(f"NTLM Decode Error: {e}")

        # Step 3: Authenticated (NTLM Type 3 or Basic)
        print(f"Received Authenticated Request: {auth_header[:20]}...")
        request_body = request.data.decode('utf-8', errors='ignore')

        # Extract OAST URL
        url_match = re.search(r'<t:URL>(.*?)</t:URL>', request_body)
        if url_match:
            target_url = url_match.group(1)
            print(f"Found callback URL: {target_url}")
            try:
                print(f"Triggering callback to {target_url}")
                requests.get(target_url, timeout=5)
                print("Triggered callback successfully")
            except Exception as e:
                print(f"Failed to trigger callback: {e}")

        # Return Success SOAP Response
        xml_response = """<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
  <s:Body>
    <m:SubscribeResponse xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages" 
                         xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types">
      <m:ResponseMessages>
        <m:SubscribeResponseMessage ResponseClass="Success">
          <m:ResponseCode>NoError</m:ResponseCode>
          <m:SubscriptionId>EgAdY2FzaDAxLnBvbHludW0ubG9jYWwSADAAAABH9P8A8A8A8A8A8A8A8A8A8A8A</m:SubscriptionId>
          <m:Watermark>Base64EncodedWatermark</m:Watermark>
        </m:SubscribeResponseMessage>
      </m:ResponseMessages>
    </m:SubscribeResponse>
  </s:Body>
</s:Envelope>"""
        return Response(xml_response, mimetype='text/xml')
    except Exception as e:
        print(f"Server Error: {e}")
        return Response(f"Internal Error: {e}", 500)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8001, threaded=True)
