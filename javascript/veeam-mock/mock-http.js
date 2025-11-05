#!/usr/bin/env node
// for validation of CVE-2023-27532 detection templates.
//
// Exposes:
//  - GET /                      -> simple HTML page (Enterprise Manager-like)
//  - GET /login.aspx            -> login page placeholder
//  - GET /api/v1/serverinfo     -> returns JSON with version and database fields (vulnerable)
//  - GET /api/v1/config/database-> returns JSON with config-like fields (simulated)
//
// This server is for validation only and does NOT implement any Veeam functionality.

const http = require('http');
const url = require('url');

const HOST = '0.0.0.0';
const PORT = process.env.PORT || 9392;

const SERVERINFO_VULN = {
  version: '11.0.1.1261',
  databaseVendor: 'Microsoft SQL Server',
  databaseContentVersion: '11.0.1.234',
  databaseName: 'VeeamBackup',
  serverName: 'VEEAM-MOCK-SERVER',
  apiVersion: '1.1-rev1',
};

const CONFIG_DATABASE = {
  VeeamConfiguration: 'MockEncryptedConfig',
  connectionString:
    'Server=mock-sql;Database=VeeamBackup;User Id=sa;Password=******;',
  encrypted: true,
  note: 'This is a simulated response for template validation only.',
};

const HTML_ROOT = `<!doctype html>
<html>
  <head><title>Veeam Backup Enterprise Manager - Mock</title></head>
  <body>
    <h1>Veeam Backup & Replication - Mock</h1>
    <p>Enterprise Manager (mock) - Version: 11.0.1.1261</p>
  </body>
</html>
`;

const LOGIN_PAGE = `<!doctype html>
<html>
  <head><title>Login - Veeam Backup Enterprise Manager</title></head>
  <body>
    <h2>Login Page (mock)</h2>
    <form><input name="username"/><input name="password" type="password"/></form>
  </body>
</html>
`;

function sendJSON(res, obj, status = 200) {
  const data = JSON.stringify(obj);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(data),
  });
  res.end(data);
}

function sendHTML(res, body, status = 200) {
  res.writeHead(status, {
    'Content-Type': 'text/html; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
  });
  res.end(body);
}

const server = http.createServer((req, res) => {
  // Silence logging by default (but you could log req.url if you need)
  const parsed = url.parse(req.url || '/', true);
  const path = (parsed.pathname || '/').toLowerCase();

  if (req.method === 'GET') {
    if (path === '/' || path === '/index.html') {
      return sendHTML(res, HTML_ROOT);
    }
    if (path === '/login.aspx') {
      return sendHTML(res, LOGIN_PAGE);
    }
    if (path === '/api/v1/serverinfo') {
      return sendJSON(res, SERVERINFO_VULN);
    }
    if (path === '/api/v1/config/database') {
      return sendJSON(res, CONFIG_DATABASE);
    }
  }

  // default 404
  res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
  res.end('Not Found');
});

server.listen(PORT, HOST, () => {
  console.log(`[http-mock] Listening on http://${HOST}:${PORT}`);
});

// graceful shutdown on SIGINT/SIGTERM
function shutdown() {
  console.log('[http-mock] Shutting down.');
  server.close(() => process.exit(0));
}
process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
