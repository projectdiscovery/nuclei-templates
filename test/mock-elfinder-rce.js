const http = require('http');
const url = require('url');

const PORT = 9999;
const files = {};

const server = http.createServer((req, res) => {
  const parsedUrl = url.parse(req.url, true);
  const pathname = parsedUrl.pathname;
  const query = parsedUrl.query;

  console.log(`[${new Date().toISOString()}] ${req.method} ${pathname}`);

  if (pathname.includes('connector')) {
    if (query.cmd === 'mkfile' && query.name && query.name.endsWith('.phar')) {
      const fileHash = 'l1_' + Buffer.from(query.name).toString('base64').replace(/[^A-Za-z0-9]/g, '').substring(0, 16);
      
      files[fileHash] = {
        name: query.name,
        content: ''
      };

      const response = {
        added: [{
          hash: fileHash,
          phash: 'l1_Lw',
          name: query.name,
          mime: 'application/octet-stream',
          ts: Math.floor(Date.now() / 1000),
          size: 0
        }]
      };

      console.log(`  [STEP 1] Created .phar file: ${query.name} -> hash: ${fileHash}`);
      
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify(response));
      return;
    }

    if (query.cmd === 'put' && query.target && query.content) {
      const decodedContent = decodeURIComponent(query.content);
      
      if (files[query.target]) {
        files[query.target].content = decodedContent;
        console.log(`  [STEP 2] Injected PHP code into ${query.target}: ${decodedContent}`);
        
        const response = {
          changed: [{
            hash: query.target,
            name: files[query.target].name,
            content: decodedContent
          }]
        };
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(response));
        return;
      }
    }

    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Unknown command' }));
    return;
  }

  if (pathname.includes('files/') && pathname.endsWith('.phar')) {
    const filename = pathname.split('/').pop();
    
    for (const [hash, file] of Object.entries(files)) {
      if (file.name === filename && file.content.includes('system')) {
        console.log(`  [STEP 3] EXECUTING .phar file: ${filename}`);
        console.log(`  [RCE] Simulating command execution...`);
        
        res.writeHead(200, { 'Content-Type': 'text/plain' });
        res.end('uid=33(www-data) gid=33(www-data) groups=33(www-data)');
        return;
      }
    }
    
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('File not found or not executable');
    return;
  }

  res.writeHead(404, { 'Content-Type': 'text/plain' });
  res.end('Not Found');
});

server.listen(PORT, '0.0.0.0', () => {
  console.log('');
  console.log('='.repeat(60));
  console.log('Mock Vulnerable elFinder v2.1.57 - Full RCE Simulation');
  console.log('='.repeat(60));
  console.log(`Server running on http://0.0.0.0:${PORT}`);
  console.log('');
  console.log('Endpoints:');
  console.log('  /elFinder/php/connector.minimal.php');
  console.log('  /elfinder/php/connector.minimal.php');
  console.log('  /php/connector.minimal.php');
  console.log('  /elFinder/files/*.phar');
  console.log('  /elfinder/files/*.phar');
  console.log('  /files/*.phar');
  console.log('');
  console.log('Waiting for exploit attempts...');
  console.log('='.repeat(60));
  console.log('');
});
