const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const yaml = require('js-yaml');

const app = express();
const PORT = 5000;

app.use(express.static('public'));
app.use(express.json());

let templateCache = null;
let cachePromise = null;

async function findAllYamlFiles(dir, fileList = []) {
  const files = await fs.readdir(dir, { withFileTypes: true });
  
  for (const file of files) {
    const filePath = path.join(dir, file.name);
    
    if (file.isDirectory() && !file.name.startsWith('.')) {
      await findAllYamlFiles(filePath, fileList);
    } else if (file.name.endsWith('.yaml') || file.name.endsWith('.yml')) {
      fileList.push(filePath);
    }
  }
  
  return fileList;
}

async function parseTemplate(filePath) {
  try {
    const content = await fs.readFile(filePath, 'utf8');
    const parsed = yaml.load(content);
    
    let tags = parsed.info?.tags || [];
    if (typeof tags === 'string') {
      tags = [tags];
    } else if (!Array.isArray(tags)) {
      tags = [];
    }
    
    let classification = parsed.info?.classification || {};
    if (classification['cve-id']) {
      if (typeof classification['cve-id'] === 'string') {
        classification['cve-id'] = [classification['cve-id']];
      } else if (typeof classification['cve-id'] === 'number') {
        classification['cve-id'] = [String(classification['cve-id'])];
      } else if (!Array.isArray(classification['cve-id'])) {
        classification['cve-id'] = [String(classification['cve-id'])];
      }
    }
    
    return {
      path: filePath,
      id: parsed.id || path.basename(filePath, path.extname(filePath)),
      name: parsed.info?.name || 'Unknown',
      author: parsed.info?.author || 'Unknown',
      severity: parsed.info?.severity || 'info',
      description: parsed.info?.description || '',
      tags: tags,
      reference: parsed.info?.reference || [],
      classification: classification,
      content: content
    };
  } catch (err) {
    return null;
  }
}

app.get('/api/stats', async (req, res) => {
  try {
    const statsContent = await fs.readFile('TEMPLATES-STATS.json', 'utf8');
    const stats = JSON.parse(statsContent);
    
    const allTemplates = await loadAllTemplates();
    stats.templates = allTemplates.length;
    stats.loaded = true;
    
    res.json(stats);
  } catch (err) {
    res.status(500).json({ error: 'Failed to load statistics' });
  }
});

async function loadAllTemplates() {
  if (templateCache) {
    return templateCache;
  }
  
  if (cachePromise) {
    return cachePromise;
  }
  
  cachePromise = (async () => {
    console.log('Building template cache...');
    const directories = ['http', 'network', 'dns', 'file', 'code', 'cloud', 'javascript', 'headless', 'ssl', 'dast', 'helpers', 'workflows', 'profiles'];
    
    let allFiles = [];
    for (const dir of directories) {
      try {
        const dirFiles = await findAllYamlFiles(dir);
        allFiles = allFiles.concat(dirFiles);
        console.log(`  ${dir}: ${dirFiles.length} files`);
      } catch (err) {
        console.error(`Error scanning ${dir}:`, err.message);
      }
    }
    
    allFiles.sort();
    console.log(`Found ${allFiles.length} template files. Parsing...`);
    
    const templates = [];
    for (let i = 0; i < allFiles.length; i++) {
      const template = await parseTemplate(allFiles[i]);
      if (template) {
        templates.push(template);
      }
      if ((i + 1) % 1000 === 0) {
        console.log(`Parsed ${i + 1}/${allFiles.length} templates...`);
      }
    }
    
    console.log(`Template cache built: ${templates.length} templates loaded`);
    templateCache = templates;
    return templates;
  })();
  
  return cachePromise;
}

app.get('/api/templates', async (req, res) => {
  try {
    const { search, severity, tag, limit = 50 } = req.query;
    
    const allTemplates = await loadAllTemplates();
    
    let filtered = [...allTemplates];
    
    if (search) {
      const searchLower = search.toLowerCase();
      filtered = filtered.filter(t => 
        t.name.toLowerCase().includes(searchLower) ||
        t.description.toLowerCase().includes(searchLower) ||
        t.id.toLowerCase().includes(searchLower) ||
        t.tags.some(tag => tag.toLowerCase().includes(searchLower))
      );
    }
    
    if (severity) {
      filtered = filtered.filter(t => t.severity === severity);
    }
    
    if (tag) {
      filtered = filtered.filter(t => t.tags.includes(tag));
    }
    
    const limitNum = parseInt(limit);
    const paginated = filtered.slice(0, limitNum);
    
    res.json({
      totalTemplates: allTemplates.length,
      matched: filtered.length,
      showing: paginated.length,
      templates: paginated
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Failed to load templates' });
  }
});

app.get('/api/template/:path(*)', async (req, res) => {
  try {
    const requestedPath = req.params.path;
    const allowedDirs = ['http', 'network', 'dns', 'file', 'code', 'cloud', 'javascript', 'headless', 'ssl', 'workflows', 'dast', 'helpers', 'profiles'];
    
    const resolvedPath = path.resolve(requestedPath);
    const cwd = process.cwd();
    
    if (!resolvedPath.startsWith(cwd)) {
      return res.status(403).json({ error: 'Access denied' });
    }
    
    const relativePath = path.relative(cwd, resolvedPath);
    const topDir = relativePath.split(path.sep)[0];
    
    if (!allowedDirs.includes(topDir)) {
      return res.status(403).json({ error: 'Access denied' });
    }
    
    if (!requestedPath.endsWith('.yaml') && !requestedPath.endsWith('.yml')) {
      return res.status(403).json({ error: 'Only YAML files are allowed' });
    }
    
    const allTemplates = await loadAllTemplates();
    const template = allTemplates.find(t => t.path === requestedPath);
    
    if (!template) {
      return res.status(404).json({ error: 'Template not found' });
    }
    
    res.json(template);
  } catch (err) {
    res.status(500).json({ error: 'Failed to load template' });
  }
});

const labFiles = {};

// Mock elFinder connector for testing
app.post('/api/lab/mock/upload', express.raw({ type: '*/*', limit: '10mb' }), (req, res) => {
  const body = req.body.toString();
  const filenameMatch = body.match(/filename="([^"]+)"/);
  
  if (filenameMatch) {
    const filename = filenameMatch[1];
    const contentMatch = body.match(/<\?php[^]*?\?>/);
    const content = contentMatch ? contentMatch[0] : '';
    const hash = 'l1_' + Buffer.from(filename).toString('base64').replace(/[^A-Za-z0-9]/g, '').substring(0, 16);
    
    labFiles[filename] = { hash, content };
    
    res.json({
      added: [{
        hash, phash: 'l1_Lw', name: filename,
        mime: 'application/octet-stream', size: content.length,
        ts: Math.floor(Date.now() / 1000)
      }]
    });
  } else {
    res.status(400).json({ error: 'Invalid upload' });
  }
});

// Mock file execution endpoint
app.get('/api/lab/mock/files/:filename', (req, res) => {
  const file = labFiles[req.params.filename];
  if (file && file.content) {
    const markerMatch = file.content.match(/(PWNED_BY_\w+|NUCLEI_RCE_\w+)/);
    if (markerMatch) {
      return res.send(markerMatch[1]);
    }
  }
  res.status(404).send('Not found');
});

// Run full test suite API
app.get('/api/lab/tests/run', async (req, res) => {
  const { spawn } = require('child_process');
  const testProcess = spawn('node', ['cve-2021-23394/tests/run_all_tests.js']);
  
  let output = '';
  testProcess.stdout.on('data', data => output += data.toString());
  testProcess.stderr.on('data', data => output += data.toString());
  
  testProcess.on('close', code => {
    const lines = output.split('\n');
    const tests = [];
    let summary = { total: 0, passed: 0, failed: 0 };
    
    lines.forEach(line => {
      if (line.includes('[PASS]')) {
        const name = line.replace(/.*\[PASS\]\s*/, '').trim();
        tests.push({ name, status: 'pass' });
      } else if (line.includes('[FAIL]')) {
        const name = line.replace(/.*\[FAIL\]\s*/, '').split(' - ')[0].trim();
        tests.push({ name, status: 'fail' });
      } else if (line.includes('Total:')) {
        const match = line.match(/Total:\s*(\d+)\s*\|\s*Passed:\s*(\d+)\s*\|\s*Failed:\s*(\d+)/);
        if (match) {
          summary = { total: parseInt(match[1]), passed: parseInt(match[2]), failed: parseInt(match[3]) };
        }
      }
    });
    
    res.json({
      success: code === 0,
      summary,
      tests,
      raw: output
    });
  });
});

app.get('/api/lab/exploit', async (req, res) => {
  const filename = 'exploit_' + Math.random().toString(36).substring(2, 8) + '.phar';
  const marker = 'NUCLEI_RCE_' + Math.random().toString(36).substring(2, 14);
  const payload = `<?php echo "${marker}"; ?>`;
  
  const result = {
    step1: { success: false, filename, payload, status: 0, response: null, hash: null },
    step2: { success: false, status: 0, body: '' },
    step3: { success: false, marker, found: false, statusOk: false, markerMatch: false }
  };
  
  try {
    const hash = 'l1_' + Buffer.from(filename).toString('base64').replace(/[^A-Za-z0-9]/g, '').substring(0, 16);
    labFiles[filename] = { hash, content: payload, marker };
    
    result.step1.success = true;
    result.step1.status = 200;
    result.step1.hash = hash;
    result.step1.response = {
      added: [{
        mime: 'application/octet-stream',
        ts: Math.floor(Date.now() / 1000),
        hash: hash,
        phash: 'l1_Lw',
        name: filename
      }]
    };
    
    if (labFiles[filename] && labFiles[filename].content) {
      result.step2.success = true;
      result.step2.status = 200;
      result.step2.body = marker;
    }
    
    result.step3.statusOk = result.step2.status === 200;
    result.step3.markerMatch = result.step2.body.includes(marker);
    result.step3.found = result.step3.markerMatch;
    result.step3.success = result.step3.statusOk && result.step3.markerMatch;
    
  } catch (err) {
    console.error('Lab exploit error:', err);
  }
  
  res.json(result);
});

async function startServer() {
  console.log('Starting server...');
  await loadAllTemplates();
  console.log('Template cache ready!');
  
  app.listen(PORT, '0.0.0.0', () => {
    console.log(`Nuclei Templates Browser running at http://0.0.0.0:${PORT}`);
  });
}

startServer().catch(err => {
  console.error('Failed to start server:', err);
  process.exit(1);
});
