#!/usr/bin/env python3
"""
Count KEV and vKEV templates for README statistics

This script counts the number of templates with 'kev' and 'vkev' tags
and outputs the statistics in a format suitable for the README.
"""

import re
import sys
from pathlib import Path
from typing import Dict, Set


def has_tag_in_file(file_path: Path, tag: str) -> bool:
    """Check if a template file has a specific tag."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Look for tags field and check if tag is present
        tags_match = re.search(r'tags:\s*([^\n]+)', content)
        if tags_match:
            tags_str = tags_match.group(1)
            # Check for tag as a standalone tag (not part of another word)
            if re.search(rf'\b{tag}\b', tags_str, re.IGNORECASE):
                return True
    except Exception as e:
        print(f"Error reading {file_path}: {e}", file=sys.stderr)

    return False


def count_kev_templates(root_dir: str) -> Dict[str, int]:
    """Count templates with KEV and vKEV tags."""
    root_path = Path(root_dir)

    # Find all YAML template files
    patterns = [
        "**/*.yaml",
        "**/*.yml"
    ]

    all_files = set()
    for pattern in patterns:
        all_files.update(root_path.glob(pattern))

    # Filter out non-template files
    template_files = [
        f for f in all_files
        if '/workflows/' not in str(f)
        and '/.github/' not in str(f)
        and '/profiles/' not in str(f)
    ]

    kev_count = 0
    vkev_count = 0
    both_count = 0
    kev_only_count = 0
    vkev_only_count = 0

    kev_files = set()
    vkev_files = set()

    for template_file in template_files:
        has_kev = has_tag_in_file(template_file, 'kev')
        has_vkev = has_tag_in_file(template_file, 'vkev')

        if has_kev:
            kev_count += 1
            kev_files.add(template_file)

        if has_vkev:
            vkev_count += 1
            vkev_files.add(template_file)

        if has_kev and has_vkev:
            both_count += 1
        elif has_kev:
            kev_only_count += 1
        elif has_vkev:
            vkev_only_count += 1

    return {
        'kev_total': kev_count,
        'vkev_total': vkev_count,
        'both': both_count,
        'kev_only': kev_only_count,
        'vkev_only': vkev_only_count,
        'total_templates': len(template_files)
    }


def main():
    """Main entry point."""
    root_dir = sys.argv[1] if len(sys.argv) > 1 else '.'

    stats = count_kev_templates(root_dir)

    # Output in a format that can be easily parsed
    print(f"KEV_TOTAL={stats['kev_total']}")
    print(f"VKEV_TOTAL={stats['vkev_total']}")
    print(f"BOTH={stats['both']}")
    print(f"KEV_ONLY={stats['kev_only']}")
    print(f"VKEV_ONLY={stats['vkev_only']}")
    print(f"TOTAL_TEMPLATES={stats['total_templates']}")

    # Also print human-readable summary to stderr for logging
    print("\n=== KEV/vKEV Statistics ===", file=sys.stderr)
    print(f"CISA KEV templates: {stats['kev_total']}", file=sys.stderr)
    print(f"VulnCheck KEV templates: {stats['vkev_total']}", file=sys.stderr)
    print(f"Templates in both: {stats['both']}", file=sys.stderr)
    print(f"CISA KEV only: {stats['kev_only']}", file=sys.stderr)
    print(f"VulnCheck KEV only: {stats['vkev_only']}", file=sys.stderr)
    print(f"Total templates scanned: {stats['total_templates']}", file=sys.stderr)


if __name__ == "__main__":
    main()
