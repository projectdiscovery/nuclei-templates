#!/usr/bin/env python3
"""
KEV Tagging Updater for Nuclei Templates

This script updates KEV (Known Exploited Vulnerabilities) tags for all CVE templates by:
1. Fetching the latest CISA KEV catalog
2. Checking each CVE template against the KEV list
3. Adding 'kev' tag to the tags field for KEV CVEs
"""

import os
import re
import sys
import time
import json
import requests
from pathlib import Path
from typing import Set, Optional

# Configuration
CISA_KEV_JSON_URL = "https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json"
TIMEOUT = 30
MAX_RETRIES = 3

class KEVUpdater:
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.updated_count = 0
        self.error_count = 0
        self.cisa_kev_cves = set()
        
    def find_cve_templates(self) -> list[Path]:
        """Find all CVE template files."""
        cve_files = []
        
        # Search for CVE templates in common directories
        patterns = [
            "**/cves/**/*.yaml",
            "**/cve-*.yaml"
        ]
        
        for pattern in patterns:
            cve_files.extend(self.root_dir.glob(pattern))
        
        return sorted(list(set(cve_files)))
    
    def extract_cve_id(self, file_path: Path) -> Optional[str]:
        """Extract CVE ID from filename or template content."""
        # First try filename
        filename = file_path.stem
        cve_match = re.search(r'CVE-\d{4}-\d+', filename, re.IGNORECASE)
        if cve_match:
            return cve_match.group().upper()
        
        # If not found in filename, try content
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                cve_match = re.search(r'cve-id:\s*(CVE-\d{4}-\d+)', content, re.IGNORECASE)
                if cve_match:
                    return cve_match.group(1).upper()
        except Exception as e:
            print(f"Error reading {file_path}: {e}")
            
        return None
    
    def fetch_cisa_kev_data(self) -> Set[str]:
        """Fetch CISA KEV catalog and return set of CVE IDs."""
        kev_cves = set()
        
        for attempt in range(MAX_RETRIES):
            try:
                print("Fetching CISA KEV catalog...")
                response = requests.get(CISA_KEV_JSON_URL, timeout=TIMEOUT)
                response.raise_for_status()
                
                data = response.json()
                
                if 'vulnerabilities' in data:
                    for vuln in data['vulnerabilities']:
                        if 'cveID' in vuln:
                            kev_cves.add(vuln['cveID'].upper())
                    
                    print(f"Retrieved {len(kev_cves)} CVEs from CISA KEV catalog")
                    break
                else:
                    print("Invalid CISA KEV data format")
                    
            except requests.RequestException as e:
                print(f"CISA KEV fetch failed (attempt {attempt + 1}/{MAX_RETRIES}): {e}")
                if attempt == MAX_RETRIES - 1:
                    print("Failed to fetch CISA KEV data")
                else:
                    time.sleep(2 * (attempt + 1))
            
            except Exception as e:
                print(f"Unexpected error fetching CISA KEV: {e}")
                break
        
        return kev_cves
    
    def has_kev_tag(self, content: str) -> bool:
        """Check if template already has KEV tag in tags field."""
        # Look for tags field and check if kev is present
        tags_match = re.search(r'tags:\s*([^\n]+)', content)
        if tags_match:
            tags_str = tags_match.group(1)
            # Check for kev as a standalone tag (not part of another word)
            if re.search(r'\bkev\b', tags_str, re.IGNORECASE):
                return True
        return False
    
    def add_kev_tag(self, content: str) -> tuple[str, bool]:
        """Add kev tag to the tags field. Returns (updated_content, was_updated)."""
        if self.has_kev_tag(content):
            return content, False
        
        # Find the tags field
        tags_match = re.search(r'(\s*tags:\s*)([^\n]+)', content)
        if tags_match:
            indent = tags_match.group(1)
            existing_tags = tags_match.group(2).rstrip()
            
            # Add kev tag at the end
            new_tags = existing_tags + ',kev'
            new_line = f"{indent}{new_tags}"
            
            updated_content = content.replace(tags_match.group(0), new_line)
            return updated_content, True
        else:
            print("Warning: No tags field found to add kev tag")
            return content, False
    
    def remove_kev_tag(self, content: str) -> tuple[str, bool]:
        """Remove kev tag from the tags field. Returns (updated_content, was_updated)."""
        if not self.has_kev_tag(content):
            return content, False
        
        # Find and update tags field
        tags_match = re.search(r'(\s*tags:\s*)([^\n]+)', content)
        if tags_match:
            indent = tags_match.group(1)
            existing_tags = tags_match.group(2)
            
            # Remove kev tag in various positions
            new_tags = existing_tags
            # Remove ,kev or kev, patterns
            new_tags = re.sub(r',\s*kev\b', '', new_tags)
            new_tags = re.sub(r'\bkev\s*,', '', new_tags) 
            new_tags = re.sub(r'^\s*kev\s*$', '', new_tags)  # kev is only tag
            new_tags = re.sub(r'^\s*kev\s*,\s*', '', new_tags)  # kev is first tag
            
            if new_tags != existing_tags:
                new_line = f"{indent}{new_tags}"
                updated_content = content.replace(tags_match.group(0), new_line)
                return updated_content, True
        
        return content, False
    
    def update_template_with_kev(self, file_path: Path, cve_id: str, is_kev: bool) -> bool:
        """Update a template file with KEV tags."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            updated = False
            
            if is_kev:
                # Add kev tag if missing
                content, tag_added = self.add_kev_tag(content)
                if tag_added:
                    print(f"Added kev tag to {file_path.name}")
                    updated = True
            else:
                # Remove kev tag if present but CVE is not in KEV catalog
                if self.has_kev_tag(content):
                    # Only remove if we're confident about our KEV data
                    if len(self.cisa_kev_cves) > 1000:  # Sanity check
                        content, tag_removed = self.remove_kev_tag(content)
                        if tag_removed:
                            print(f"Removed kev tag from {file_path.name} (no longer in KEV catalog)")
                            updated = True
                    else:
                        print(f"Warning: {cve_id} has kev tag but not in current KEV catalog (keeping tag)")
            
            # Write updated content if changes were made
            if updated:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                return True
            
            return False
            
        except Exception as e:
            print(f"Error updating {file_path}: {e}")
            self.error_count += 1
            return False
    
    def run(self):
        """Main execution function."""
        print("Starting KEV tagging update...")
        
        # Fetch KEV data
        self.cisa_kev_cves = self.fetch_cisa_kev_data()
        
        if not self.cisa_kev_cves:
            print("No CISA KEV data available, exiting")
            return
        
        # Find all CVE templates
        template_files = self.find_cve_templates()
        print(f"Found {len(template_files)} CVE template files")
        
        if not template_files:
            print("No CVE templates found!")
            return
        
        # Process templates
        kev_found = 0
        non_kev_with_tag = 0
        
        for file_path in template_files:
            cve_id = self.extract_cve_id(file_path)
            if cve_id:
                is_kev = cve_id in self.cisa_kev_cves
                
                if is_kev:
                    kev_found += 1
                
                if self.update_template_with_kev(file_path, cve_id, is_kev):
                    self.updated_count += 1
                
                # Track non-KEV CVEs that have kev tags for reporting
                if not is_kev and self.has_kev_tag(open(file_path, 'r').read()):
                    non_kev_with_tag += 1
            else:
                print(f"Could not extract CVE ID from {file_path}")
        
        print(f"\nKEV update complete!")
        print(f"Templates updated: {self.updated_count}")
        print(f"KEV CVEs found in templates: {kev_found}")
        print(f"CISA KEV catalog size: {len(self.cisa_kev_cves)}")
        print(f"Non-KEV templates with kev tags: {non_kev_with_tag}")
        print(f"Errors: {self.error_count}")

def main():
    """Main entry point."""
    if len(sys.argv) > 1:
        root_dir = sys.argv[1]
    else:
        root_dir = os.getcwd()
    
    updater = KEVUpdater(root_dir)
    updater.run()

if __name__ == "__main__":
    main()