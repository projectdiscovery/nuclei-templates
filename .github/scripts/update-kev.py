#!/usr/bin/env python3
"""
KEV Tagging Updater for Nuclei Templates

This script updates KEV (Known Exploited Vulnerabilities) tags for all CVE templates by:
1. Fetching the latest CISA KEV catalog
2. Checking each CVE template against the KEV list
3. Adding 'kev' tag and metadata for KEV CVEs
4. Adding 'vkev' metadata for VulnCheck KEV CVEs (requires API access)
"""

import os
import re
import sys
import time
import json
import yaml
import requests
from pathlib import Path
from typing import Dict, List, Set, Optional

# Configuration
CISA_KEV_JSON_URL = "https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json"
TIMEOUT = 30
MAX_RETRIES = 3

# VulnCheck KEV configuration (placeholder - requires API key)
VULNCHECK_KEV_API = "https://api.vulncheck.com/v3/kev"  # Placeholder
VULNCHECK_API_KEY = os.environ.get('VULNCHECK_API_KEY')  # Optional API key

class KEVUpdater:
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.updated_count = 0
        self.error_count = 0
        self.cisa_kev_cves = set()
        self.vulncheck_kev_cves = set()
        
    def find_cve_templates(self) -> List[Path]:
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
    
    def fetch_vulncheck_kev_data(self) -> Set[str]:
        """Fetch VulnCheck KEV data (requires API key)."""
        if not VULNCHECK_API_KEY:
            print("VulnCheck API key not provided, skipping vKEV updates")
            return set()
        
        # Placeholder implementation - would need actual VulnCheck API integration
        print("VulnCheck KEV integration not implemented yet")
        print("To enable vKEV updates, implement VulnCheck API integration")
        
        # TODO: Implement VulnCheck API integration
        # This would require:
        # 1. Authentication with VulnCheck API
        # 2. Fetching their KEV dataset
        # 3. Extracting CVE IDs from their format
        
        return set()
    
    def has_kev_tag(self, content: str) -> bool:
        """Check if template already has KEV tag."""
        # Check in tags field
        tags_match = re.search(r'tags:\s*([^\n]+)', content)
        if tags_match:
            tags_str = tags_match.group(1)
            if re.search(r'\bkev\b', tags_str, re.IGNORECASE):
                return True
        return False
    
    def has_kev_metadata(self, content: str) -> bool:
        """Check if template already has KEV metadata."""
        return bool(re.search(r'kev:\s*true', content, re.IGNORECASE))
    
    def has_vkev_metadata(self, content: str) -> bool:
        """Check if template already has vKEV metadata."""
        return bool(re.search(r'vkev:\s*true', content, re.IGNORECASE))
    
    def update_template_with_kev(self, file_path: Path, cve_id: str, is_cisa_kev: bool, is_vulncheck_kev: bool) -> bool:
        """Update a template file with KEV tags and metadata."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            updated = False
            
            # Check current state
            has_kev_tag = self.has_kev_tag(content)
            has_kev_meta = self.has_kev_metadata(content)
            has_vkev_meta = self.has_vkev_metadata(content)
            
            # Update CISA KEV
            if is_cisa_kev:
                # Add kev tag if missing
                if not has_kev_tag:
                    tags_match = re.search(r'(\s+tags:\s*)([^\n]+)', content)
                    if tags_match:
                        indent = tags_match.group(1)
                        existing_tags = tags_match.group(2)
                        # Add kev tag
                        new_tags = existing_tags.rstrip() + ',kev'
                        content = content.replace(tags_match.group(0), f"{indent}{new_tags}")
                        updated = True
                        print(f"Added kev tag to {file_path.name}")
                
                # Add kev metadata if missing
                if not has_kev_meta:
                    # Find metadata section
                    metadata_match = re.search(r'(\s+metadata:\s*\n)', content)
                    if metadata_match:
                        # Add kev: true after metadata section start
                        insertion_point = metadata_match.end()
                        indent = "    "  # Standard YAML indent
                        kev_meta = f"{indent}kev: true\n"
                        content = content[:insertion_point] + kev_meta + content[insertion_point:]
                        updated = True
                        print(f"Added kev metadata to {file_path.name}")
                    else:
                        # Create metadata section if it doesn't exist
                        # Find a good place to insert it (after classification or before tags)
                        classification_match = re.search(r'(\s+classification:.*?\n)(\s+(?:metadata|tags))', content, re.DOTALL)
                        if classification_match:
                            insertion_point = classification_match.start(2)
                            metadata_section = f"  metadata:\n    kev: true\n"
                            content = content[:insertion_point] + metadata_section + content[insertion_point:]
                            updated = True
                            print(f"Created metadata section with kev for {file_path.name}")
            
            # Update VulnCheck KEV
            if is_vulncheck_kev and not has_vkev_meta:
                # Add vkev metadata
                metadata_match = re.search(r'(\s+metadata:\s*\n)', content)
                if metadata_match:
                    # Find a good spot within metadata section
                    insertion_point = metadata_match.end()
                    # Look for existing metadata to maintain order
                    next_field_match = re.search(r'\n(\s+\w+:)', content[insertion_point:])
                    if next_field_match:
                        # Insert before next field
                        actual_insertion = insertion_point + next_field_match.start()
                        indent = "    "
                        vkev_meta = f"{indent}vkev: true\n"
                        content = content[:actual_insertion] + vkev_meta + content[actual_insertion:]
                    else:
                        # Insert at end of metadata section
                        indent = "    "
                        vkev_meta = f"{indent}vkev: true\n"
                        content = content[:insertion_point] + vkev_meta + content[insertion_point:]
                    updated = True
                    print(f"Added vkev metadata to {file_path.name}")
                elif is_cisa_kev:  # Only create metadata if we also added kev
                    # Metadata section was already created above
                    # Add vkev to existing metadata
                    kev_meta_match = re.search(r'(\s+kev: true\n)', content)
                    if kev_meta_match:
                        insertion_point = kev_meta_match.end()
                        indent = "    "
                        vkev_meta = f"{indent}vkev: true\n"
                        content = content[:insertion_point] + vkev_meta + content[insertion_point:]
                        updated = True
                        print(f"Added vkev metadata after kev for {file_path.name}")
            
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
    
    def remove_kev_tags(self, file_path: Path, cve_id: str) -> bool:
        """Remove KEV tags from templates that are no longer in KEV lists."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            updated = False
            
            # Remove kev tag if present
            if self.has_kev_tag(content):
                # Remove from tags
                content = re.sub(r',\s*kev\b', '', content)  # Remove ,kev
                content = re.sub(r'\bkev\s*,', '', content)  # Remove kev,
                content = re.sub(r'\s+kev\b(?![\w-])', '', content)  # Remove standalone kev
                updated = True
                print(f"Removed kev tag from {file_path.name}")
            
            # Remove kev metadata if present
            if self.has_kev_metadata(content):
                content = re.sub(r'\s+kev:\s*true\s*\n', '\n', content)
                updated = True
                print(f"Removed kev metadata from {file_path.name}")
            
            # Note: We don't remove vkev metadata automatically as VulnCheck data might be incomplete
            
            if updated:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                return True
            
            return False
            
        except Exception as e:
            print(f"Error cleaning KEV tags from {file_path}: {e}")
            return False
    
    def run(self):
        """Main execution function."""
        print("Starting KEV tagging update...")
        
        # Fetch KEV data
        self.cisa_kev_cves = self.fetch_cisa_kev_data()
        self.vulncheck_kev_cves = self.fetch_vulncheck_kev_data()
        
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
        for file_path in template_files:
            cve_id = self.extract_cve_id(file_path)
            if cve_id:
                is_cisa_kev = cve_id in self.cisa_kev_cves
                is_vulncheck_kev = cve_id in self.vulncheck_kev_cves
                
                if is_cisa_kev or is_vulncheck_kev:
                    # Add KEV tags/metadata
                    if self.update_template_with_kev(file_path, cve_id, is_cisa_kev, is_vulncheck_kev):
                        self.updated_count += 1
                elif self.has_kev_tag(open(file_path, 'r').read()) or self.has_kev_metadata(open(file_path, 'r').read()):
                    # Template has KEV tags but CVE is not in current KEV list
                    # Only remove if we're confident about our data
                    if len(self.cisa_kev_cves) > 1000:  # Sanity check
                        print(f"Warning: {cve_id} has KEV tags but not in current KEV catalog")
                        # Uncomment the line below to automatically remove outdated KEV tags
                        # self.remove_kev_tags(file_path, cve_id)
            else:
                print(f"Could not extract CVE ID from {file_path}")
        
        print(f"\nKEV update complete!")
        print(f"Templates updated: {self.updated_count}")
        print(f"CISA KEV CVEs processed: {len(self.cisa_kev_cves)}")
        print(f"VulnCheck KEV CVEs processed: {len(self.vulncheck_kev_cves)}")
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