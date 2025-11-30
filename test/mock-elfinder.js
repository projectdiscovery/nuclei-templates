const http = require('http');
const url = require('url');

const PORT = 8888;

const server = http.createServer((req, res) => {
  const parsedUrl = url.parse(req.url, true);
  const pathname = parsedUrl.pathname;
  const query = parsedUrl.query;

  console.log(`[${new Date().toISOString()}] ${req.method} ${req.url}`);

  if (pathname.includes('connector.minimal.php') || pathname.includes('connector.php')) {
    if (query.cmd === 'mkfile' && query.name && query.name.endsWith('.phar')) {
      const fileHash = 'l1_' + Buffer.from(query.name).toString('base64').replace(/[^A-Za-z0-9]/g, '').substring(0, 12);
      
      const response = {
        added: [{
          hash: fileHash,
          phash: 'l1_Lw',
          name: query.name,
          mime: 'application/octet-stream',
          ts: Math.floor(Date.now() / 1000),
          size: 0,
          read: 1,
          write: 1,
          locked: 0
        }]
      };

      console.log(`[VULNERABLE] Created .phar file: ${query.name} with hash: ${fileHash}`);
      
      res.writeHead(200, {
        'Content-Type': 'application/json',
        'X-elFinder-Version': '2.1.57'
      });
      res.end(JSON.stringify(response));
      return;
    }

    if (query.cmd === 'put' && query.target && query.content) {
      console.log(`[VULNERABLE] PHP code injected into file: ${query.target}`);
      console.log(`[VULNERABLE] Content: ${query.content}`);
      
      const response = {
        changed: [{
          hash: query.target,
          content: query.content
        }]
      };
      
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify(response));
      return;
    }

    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Unknown command' }));
    return;
  }

  res.writeHead(404, { 'Content-Type': 'text/plain' });
  res.end('Not Found');
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`\n========================================`);
  console.log(`Mock Vulnerable elFinder Server v2.1.57`);
  console.log(`========================================`);
  console.log(`Listening on http://0.0.0.0:${PORT}`);
  console.log(`\nVulnerable endpoints:`);
  console.log(`  - /elFinder/php/connector.minimal.php`);
  console.log(`  - /elfinder/php/connector.minimal.php`);
  console.log(`  - /php/connector.minimal.php`);
  console.log(`\nWaiting for nuclei scan...\n`);
});
