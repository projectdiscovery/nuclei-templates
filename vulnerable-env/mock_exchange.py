from flask import Flask, request, Response

app = Flask(__name__)

@app.route('/')
def home():
    return "Mock Exchange Server for CVE-2018-8581 is running. EWS endpoint is at /ews/exchange.asmx"

@app.route('/ews/exchange.asmx', methods=['POST'])
def ews():
    print(f"Received request to EWS: {request.data}")
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
