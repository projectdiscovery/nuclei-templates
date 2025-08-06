#!/usr/bin/env python3
import glob
import subprocess

def countTpl(path):
	return len(glob.glob(path + "/*.*"))

def command(args, start=None, end=None):
	return "\n".join(subprocess.run(args, text=True, capture_output=True).stdout.split("\n")[start:end])[:-1]

def get_top10():
	HEADER = "## Nuclei Templates Top 10 statistics\n\n"
	TOP10 = command(["cat", "TOP-10.md"])
	return HEADER + TOP10 if len(TOP10) > 0 else ""

if __name__ == "__main__":
	version = command(["git", "describe", "--tags", "--abbrev=0"])
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
