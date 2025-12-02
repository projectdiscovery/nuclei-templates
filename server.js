const express = require('express');
const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');

const app = express();
const PORT = 5000;

app.use((req, res, next) => {
  res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  res.setHeader('Pragma', 'no-cache');
  res.setHeader('Expires', '0');
  next();
});

app.use(express.static('public'));
app.use(express.json());

const TEMPLATE_DIRS = ['http', 'network', 'dns', 'file', 'cloud', 'code', 'javascript', 'dast', 'headless', 'ssl', 'workflows'];

let templateCache = null;
let cacheTimestamp = null;
const CACHE_DURATION = 60000;

function getAllYamlFiles(dir, fileList = []) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory()) {
      getAllYamlFiles(filePath, fileList);
    } else if (file.endsWith('.yaml') || file.endsWith('.yml')) {
      fileList.push(filePath);
    }
  });
  
  return fileList;
}

function parseTemplate(filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    const template = yaml.load(content);
    
    let tags = template.info?.tags || [];
    if (typeof tags === 'string') {
      tags = tags.split(',').map(t => t.trim());
    } else if (!Array.isArray(tags)) {
      tags = [];
    }
    
    return {
      path: filePath,
      id: template.id || 'unknown',
      name: template.info?.name || 'Unnamed Template',
      author: template.info?.author || 'Unknown',
      severity: template.info?.severity || 'info',
      description: template.info?.description || '',
      tags: tags,
      reference: template.info?.reference || [],
      classification: template.info?.classification || {},
      content: content
    };
  } catch (error) {
    console.error(`Error parsing ${filePath}:`, error.message);
    return null;
  }
}

function loadAllTemplates() {
  const templates = [];
  for (const dir of TEMPLATE_DIRS) {
    if (fs.existsSync(dir)) {
      const files = getAllYamlFiles(dir);
      const parsed = files
        .map(f => parseTemplate(f))
        .filter(t => t !== null);
      templates.push(...parsed);
    }
  }
  return templates;
}

app.get('/api/templates', (req, res) => {
  try {
    const { category, severity, search, limit = 100 } = req.query;
    
    if (!templateCache || !cacheTimestamp || (Date.now() - cacheTimestamp > CACHE_DURATION)) {
      console.log('Rebuilding template cache...');
      templateCache = loadAllTemplates();
      cacheTimestamp = Date.now();
      console.log(`Cached ${templateCache.length} templates`);
    }
    
    let templates = category && TEMPLATE_DIRS.includes(category)
      ? templateCache.filter(t => t.path.startsWith(category + '/'))
      : templateCache;
    
    if (severity) {
      templates = templates.filter(t => t.severity === severity);
    }
    
    if (search) {
      const searchLower = search.toLowerCase();
      templates = templates.filter(t => 
        t.name.toLowerCase().includes(searchLower) ||
        t.id.toLowerCase().includes(searchLower) ||
        t.description.toLowerCase().includes(searchLower) ||
        t.tags.some(tag => tag.toLowerCase().includes(searchLower))
      );
    }
    
    templates = templates.slice(0, parseInt(limit));
    
    res.json({
      count: templates.length,
      templates: templates.map(t => ({
        path: t.path,
        id: t.id,
        name: t.name,
        author: t.author,
        severity: t.severity,
        description: t.description,
        tags: t.tags,
        classification: t.classification
      }))
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.get('/api/template/:id', (req, res) => {
  try {
    const templateId = req.params.id;
    
    for (const dir of TEMPLATE_DIRS) {
      if (fs.existsSync(dir)) {
        const files = getAllYamlFiles(dir);
        
        for (const file of files) {
          const template = parseTemplate(file);
          if (template && template.id === templateId) {
            return res.json(template);
          }
        }
      }
    }
    
    res.status(404).json({ error: 'Template not found' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.get('/api/stats', (req, res) => {
  try {
    const stats = {
      total: 0,
      byCategory: {},
      bySeverity: {},
      byTag: {}
    };
    
    for (const dir of TEMPLATE_DIRS) {
      if (fs.existsSync(dir)) {
        const files = getAllYamlFiles(dir);
        stats.byCategory[dir] = files.length;
        stats.total += files.length;
      }
    }
    
    res.json(stats);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Nuclei Templates Browser running on http://0.0.0.0:${PORT}`);
});
