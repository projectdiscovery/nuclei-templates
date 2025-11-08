from flask import Flask, Response, request
app = Flask(__name__)

# Simple route to simulate exposed config that contains credential-like fields
@app.route('/api/Config', methods=['GET'])
def config():
    body = """
    {
      "Config": {
        "Database": {
          "Username": "veeam_admin",
          "EncryptedPassword": "AQAAANCMnd8BFdERjHoAwE/Cl+...",
          "Host": "127.0.0.1"
        },
        "Credentials": [
          {"Name":"svc_backup","Username":"svc_backup","EncryptedPassword":"BAAAANCMnd8B..."}
        ]
      }
    }
    """
    return Response(body, status=200, mimetype='application/json')

@app.route('/EnterpriseManager/api/config', methods=['GET'])
def enterprise_config():
    # simulate another path
    body = """
    <Config>
      <Credentials>
        <Credential>
          <Username>domain\\veeamuser</Username>
          <EncryptedPassword>U2FsdGVkX1+exampleEncrypted</EncryptedPassword>
        </Credential>
      </Credentials>
    </Config>
    """
    return Response(body, status=200, mimetype='application/xml')

@app.route('/EnterpriseManager/ConfigService.svc', methods=['POST'])
def svc():
    # respond with SOAP-style body
    body = """<?xml version="1.0"?>
    <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
      <s:Body>
        <GetConfigResponse>
          <Config>
            <Credentials>
              <Username>veeam_admin</Username>
              <EncryptedPassword>U2FsdGVkX1+exampleEncryptedSOAP</EncryptedPassword>
            </Credentials>
          </Config>
        </GetConfigResponse>
      </s:Body>
    </s:Envelope>
    """
    return Response(body, status=200, mimetype='application/xml')

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080)
