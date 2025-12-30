from flask import Flask, request, Response

app = Flask(__name__)

@app.route('/')
def home():
    return "Mock Exchange Server for CVE-2018-8581 is running. EWS endpoint is at /ews/exchange.asmx"

@app.route('/ews/exchange.asmx', methods=['POST'])
def ews():
    print(f"Received request to EWS: {request.data}")
    
    # Simple regex to find the URL in the XML
    import re
    import requests
    url_match = re.search(r'<t:URL>(.*?)</t:URL>', request.data.decode('utf-8'))
    if url_match:
        target_url = url_match.group(1)
        print(f"Found callback URL: {target_url}")
        try:
            # Trigger the interaction
            requests.get(target_url, timeout=5)
            print("Triggered callback")
        except Exception as e:
            print(f"Failed to trigger callback: {e}")

    xml_response = """<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
  <s:Body>
    <m:SubscribeResponse xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages" 
                         xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types">
      <m:ResponseMessages>
        <m:SubscribeResponseMessage ResponseClass="Success">
          <m:ResponseCode>NoError</m:ResponseCode>
          <m:SubscriptionId>Id="inbox"</m:SubscriptionId>
          <m:Watermark>Base64EncodedWatermark</m:Watermark>
        </m:SubscribeResponseMessage>
      </m:ResponseMessages>
    </m:SubscribeResponse>
  </s:Body>
</s:Envelope>"""
    return Response(xml_response, mimetype='text/xml')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8001)
