#!/usr/bin/env python3
import glob
import subprocess
import re

def countTpl(path):
	return len(glob.glob(path + "/*.*"))

def command(args, start=None, end=None):
	return "\n".join(subprocess.run(args, text=True, capture_output=True).stdout.split("\n")[start:end])[:-1]

def get_top10():
	HEADER = "## Nuclei Templates Top 10 statistics\n\n"
	TOP10 = command(["cat", "TOP-10.md"])
	return HEADER + TOP10 if len(TOP10) > 0 else ""

def get_kev_stats():
	"""Get KEV and vKEV statistics by running the count-kev-stats.py script."""
	try:
		result = subprocess.run(
			["python3", ".github/scripts/count-kev-stats.py"],
			text=True,
			capture_output=True,
			timeout=120
		)

		# Parse the output
		stats = {}
		for line in result.stdout.strip().split('\n'):
			if '=' in line:
				key, value = line.split('=')
				stats[key.lower()] = int(value)

		return stats
	except Exception as e:
		print(f"Error getting KEV stats: {e}")
		return {
			'kev_total': 0,
			'vkev_total': 0,
			'both': 0,
			'kev_only': 0,
			'vkev_only': 0,
			'total_templates': 0
		}

if __name__ == "__main__":
	version = command(["git", "describe", "--tags", "--abbrev=0"])
	kev_stats = get_kev_stats()
	template = eval(open(".github/scripts/README.tmpl", "r").read())

	print(template)
	
	# Update main README.md
	f = open("README.md", "w")
	f.write(template)
	f.close()
	
	# Update localized README files with their respective templates
	localized_files = {
		"README_CN.md": ".github/scripts/README_CN.tmpl",
		"README_JA.md": ".github/scripts/README_JA.tmpl", 
		"README_KR.md": ".github/scripts/README_KR.tmpl"
	}
	
	for readme_file, template_file in localized_files.items():
		try:
			localized_template = eval(open(template_file, "r").read())
			f = open(readme_file, "w")
			f.write(localized_template)
			f.close()
		except FileNotFoundError:
			print(f"Template {template_file} not found, skipping {readme_file}")
			continue
