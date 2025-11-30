const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const yaml = require('js-yaml');

const app = express();
const PORT = 5000;

app.use(express.static('public'));
app.use(express.json());

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
    
    return {
      path: filePath,
      id: parsed.id || path.basename(filePath, path.extname(filePath)),
      name: parsed.info?.name || 'Unknown',
      author: parsed.info?.author || 'Unknown',
      severity: parsed.info?.severity || 'info',
      description: parsed.info?.description || '',
      tags: tags,
      reference: parsed.info?.reference || [],
      classification: parsed.info?.classification || {},
      content: content
    };
  } catch (err) {
    return null;
  }
}

app.get('/api/stats', async (req, res) => {
  try {
    const statsContent = await fs.readFile('TEMPLATES-STATS.json', 'utf8');
    res.json(JSON.parse(statsContent));
  } catch (err) {
    res.status(500).json({ error: 'Failed to load statistics' });
  }
});

app.get('/api/templates', async (req, res) => {
  try {
    const { search, severity, tag, limit = 50 } = req.query;
    const directories = ['http', 'network', 'dns', 'file', 'code', 'cloud', 'javascript', 'headless', 'ssl'];
    
    let allFiles = [];
    for (const dir of directories) {
      try {
        const dirFiles = await findAllYamlFiles(dir);
        allFiles = allFiles.concat(dirFiles);
      } catch (err) {
      }
    }
    
    allFiles = allFiles.slice(0, parseInt(limit));
    
    const templates = await Promise.all(
      allFiles.map(file => parseTemplate(file))
    );
    
    let filtered = templates.filter(t => t !== null);
    
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
    
    res.json({
      total: filtered.length,
      templates: filtered
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Failed to load templates' });
  }
});

app.get('/api/template/:path(*)', async (req, res) => {
  try {
    const filePath = req.params.path;
    const template = await parseTemplate(filePath);
    
    if (!template) {
      return res.status(404).json({ error: 'Template not found' });
    }
    
    res.json(template);
  } catch (err) {
    res.status(500).json({ error: 'Failed to load template' });
  }
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Nuclei Templates Browser running at http://0.0.0.0:${PORT}`);
});
