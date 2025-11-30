async function loadStats() {
    try {
        const response = await fetch('/api/stats');
        const data = await response.json();
        
        const totalTemplates = data.templates || 0;
        const cveTag = data.tags?.find(t => t.name === 'cve');
        const kevTag = data.tags?.find(t => t.name === 'kev');
        const totalDirs = data.directories || 0;
        
        document.getElementById('total-templates').textContent = totalTemplates.toLocaleString();
        document.getElementById('total-cves').textContent = cveTag ? cveTag.count.toLocaleString() : '0';
        document.getElementById('total-kev').textContent = kevTag ? kevTag.count.toLocaleString() : '0';
        document.getElementById('total-dirs').textContent = totalDirs.toLocaleString();
    } catch (err) {
        console.error('Failed to load stats:', err);
    }
}

async function searchTemplates() {
    const searchTerm = document.getElementById('searchInput').value;
    const severity = document.getElementById('severityFilter').value;
    const limit = document.getElementById('limitInput').value;
    const resultsDiv = document.getElementById('results');
    const loadingDiv = document.getElementById('loading');
    
    loadingDiv.style.display = 'block';
    resultsDiv.innerHTML = '';
    
    try {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (severity) params.append('severity', severity);
        if (limit) params.append('limit', limit);
        
        const response = await fetch(`/api/templates?${params}`);
        const data = await response.json();
        
        loadingDiv.style.display = 'none';
        
        if (!data.templates || data.templates.length === 0) {
            resultsDiv.innerHTML = `
                <div class="no-results">
                    <h3>No templates found</h3>
                    <p>Try adjusting your search criteria</p>
                </div>
            `;
            return;
        }
        
        resultsDiv.innerHTML = data.templates.map(template => `
            <div class="template-card">
                <div class="template-header">
                    <div>
                        <div class="template-title">${escapeHtml(template.name)}</div>
                        <div class="template-id">${escapeHtml(template.id)}</div>
                    </div>
                    <span class="severity-badge severity-${template.severity}">
                        ${template.severity}
                    </span>
                </div>
                
                ${template.description ? `
                    <div class="template-description">
                        ${escapeHtml(template.description)}
                    </div>
                ` : ''}
                
                <div class="template-meta">
                    <div class="meta-item">
                        <strong>Author:</strong> ${escapeHtml(template.author)}
                    </div>
                    ${template.classification?.['cve-id'] ? `
                        <div class="meta-item">
                            <strong>CVE:</strong> ${escapeHtml(template.classification['cve-id'].join(', '))}
                        </div>
                    ` : ''}
                </div>
                
                ${template.tags && template.tags.length > 0 ? `
                    <div class="tags">
                        ${template.tags.map(tag => `
                            <span class="tag">${escapeHtml(tag)}</span>
                        `).join('')}
                    </div>
                ` : ''}
                
                <div class="template-path">${escapeHtml(template.path)}</div>
            </div>
        `).join('');
        
    } catch (err) {
        loadingDiv.style.display = 'none';
        resultsDiv.innerHTML = `
            <div class="no-results">
                <h3>Error loading templates</h3>
                <p>${escapeHtml(err.message)}</p>
            </div>
        `;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.getElementById('searchBtn').addEventListener('click', searchTemplates);
document.getElementById('searchInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        searchTemplates();
    }
});

loadStats();
searchTemplates();
