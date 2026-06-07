#!/usr/bin/env python3
"""
EPSS Score Updater for Nuclei Templates

This script updates EPSS scores for all CVE templates by:
1. Scanning all CVE YAML files
2. Extracting CVE IDs
3. Fetching latest EPSS scores from the FIRST API
4. Updating the templates with new scores
"""

import os
import re
import sys
import time
import yaml
import requests
from pathlib import Path
from typing import Dict, List, Tuple, Optional

# Configuration
EPSS_API_URL = "https://api.first.org/data/v1/epss"
BATCH_SIZE = 50  # API limit for batch requests
RATE_LIMIT_DELAY = 1  # seconds between API calls
MAX_RETRIES = 3
TIMEOUT = 30

class EPSSUpdater:
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.updated_count = 0
        self.error_count = 0
        
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
        
        # Filter duplicates and sort
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
    
    def fetch_epss_scores(self, cve_ids: List[str]) -> Dict[str, Dict[str, str]]:
        """Fetch EPSS scores from API for multiple CVEs."""
        epss_data = {}
        
        # Process in batches
        for i in range(0, len(cve_ids), BATCH_SIZE):
            batch = cve_ids[i:i+BATCH_SIZE]
            cve_param = ','.join(batch)
            
            for attempt in range(MAX_RETRIES):
                try:
                    print(f"Fetching EPSS data for batch {i//BATCH_SIZE + 1} ({len(batch)} CVEs)...")
                    
                    response = requests.get(
                        EPSS_API_URL,
                        params={'cve': cve_param},
                        timeout=TIMEOUT
                    )
                    response.raise_for_status()
                    
                    data = response.json()
                    
                    if data.get('status') == 'OK' and 'data' in data:
                        for item in data['data']:
                            cve_id = item['cve'].upper()
                            epss_data[cve_id] = {
                                'epss': item['epss'],
                                'percentile': item['percentile']
                            }
                    
                    break  # Success, exit retry loop
                    
                except requests.RequestException as e:
                    print(f"API request failed (attempt {attempt + 1}/{MAX_RETRIES}): {e}")
                    if attempt == MAX_RETRIES - 1:
                        print(f"Failed to fetch EPSS data for batch: {batch}")
                    else:
                        time.sleep(RATE_LIMIT_DELAY * (attempt + 1))
                
                except Exception as e:
                    print(f"Unexpected error: {e}")
                    break
            
            # Rate limiting between batches
            if i + BATCH_SIZE < len(cve_ids):
                time.sleep(RATE_LIMIT_DELAY)
        
        return epss_data
    
    def update_template(self, file_path: Path, cve_id: str, epss_data: Dict[str, str]) -> bool:
        """Update a template file with new EPSS scores."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Parse YAML to get structure
            try:
                template_data = yaml.safe_load(content)
            except yaml.YAMLError as e:
                print(f"YAML parsing error in {file_path}: {e}")
                return False
            
            if not template_data or 'info' not in template_data:
                print(f"Invalid template structure in {file_path}")
                return False
            
            # Ensure classification section exists
            if 'classification' not in template_data['info']:
                template_data['info']['classification'] = {}
            
            classification = template_data['info']['classification']
            
            # Convert EPSS values to float for comparison
            new_epss = float(epss_data['epss'])
            new_percentile = float(epss_data['percentile'])
            
            current_epss = classification.get('epss-score', 0)
            current_percentile = classification.get('epss-percentile', 0)
            
            # Check if update is needed
            if (abs(new_epss - float(current_epss)) < 0.00001 and 
                abs(new_percentile - float(current_percentile)) < 0.00001):
                return False  # No significant change
            
            # Update scores using string replacement to preserve formatting
            epss_score_str = f"{new_epss:.5f}".rstrip('0').rstrip('.')
            percentile_str = f"{new_percentile:.5f}".rstrip('0').rstrip('.')
            
            # Update epss-score
            if 'epss-score:' in content:
                content = re.sub(
                    r'(\s+epss-score:\s*)[0-9.]+',
                    fr'\g<1>{epss_score_str}',
                    content
                )
            else:
                # Add after cve-id if exists
                if 'cve-id:' in content:
                    content = re.sub(
                        r'(\s+cve-id:\s*CVE-\d{4}-\d+\s*\n)',
                        fr'\g<1>    epss-score: {epss_score_str}\n',
                        content
                    )
            
            # Update epss-percentile
            if 'epss-percentile:' in content:
                content = re.sub(
                    r'(\s+epss-percentile:\s*)[0-9.]+',
                    fr'\g<1>{percentile_str}',
                    content
                )
            else:
                # Add after epss-score
                content = re.sub(
                    r'(\s+epss-score:\s*[0-9.]+\s*\n)',
                    fr'\g<1>    epss-percentile: {percentile_str}\n',
                    content
                )
            
            # Write updated content
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            print(f"Updated {file_path.name}: EPSS={epss_score_str}, Percentile={percentile_str}")
            return True
            
        except Exception as e:
            print(f"Error updating {file_path}: {e}")
            return False
    
    def run(self):
        """Main execution function."""
        print("Starting EPSS score update...")
        
        # Find all CVE templates
        template_files = self.find_cve_templates()
        print(f"Found {len(template_files)} CVE template files")
        
        if not template_files:
            print("No CVE templates found!")
            return
        
        # Extract CVE IDs and map to files
        cve_to_files = {}
        for file_path in template_files:
            cve_id = self.extract_cve_id(file_path)
            if cve_id:
                if cve_id not in cve_to_files:
                    cve_to_files[cve_id] = []
                cve_to_files[cve_id].append(file_path)
            else:
                print(f"Could not extract CVE ID from {file_path}")
        
        print(f"Found {len(cve_to_files)} unique CVE IDs")
        
        # Fetch EPSS scores
        all_cve_ids = list(cve_to_files.keys())
        epss_data = self.fetch_epss_scores(all_cve_ids)
        
        print(f"Retrieved EPSS data for {len(epss_data)} CVEs")
        
        # Update templates
        for cve_id, files in cve_to_files.items():
            if cve_id in epss_data:
                for file_path in files:
                    if self.update_template(file_path, cve_id, epss_data[cve_id]):
                        self.updated_count += 1
            else:
                print(f"No EPSS data found for {cve_id}")
                self.error_count += 1
        
        print(f"\nUpdate complete!")
        print(f"Templates updated: {self.updated_count}")
        print(f"Errors: {self.error_count}")

def main():
    """Main entry point."""
    if len(sys.argv) > 1:
        root_dir = sys.argv[1]
    else:
        root_dir = os.getcwd()
    
    updater = EPSSUpdater(root_dir)
    updater.run()

if __name__ == "__main__":
    main()