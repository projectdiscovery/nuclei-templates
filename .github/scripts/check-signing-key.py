#!/usr/bin/env python3
"""Fail if a code or javascript template is signed with a non-ProjectDiscovery key.

Nuclei refuses to run code and javascript templates whose signature does not
verify, and it also refuses to *re-sign* one that already carries a signature:

    could not sign 'x.yaml': cause="re-signing code templates are not allowed
    for security reasons."

So a template contributed with the author's own signing key can never be picked
up by the signing workflow. It stays permanently unrunnable, and because http
and network templates only warn, the breakage is easy to miss. CVE-2025-71260
sat broken from March 2026 until it was found by hand.

The fix is always the same: drop the `# digest:` line so the signing workflow
can apply the official key on the next push to main. This check catches it at
review time instead.

A template with no digest at all passes — that is the expected state for a new
or freshly stripped template awaiting signing.
"""

import argparse
import pathlib
import re
import subprocess
import sys

PD_KEY = "922c64590222798bb761d5b6d8e72950"
ROOTS = ("code", "javascript")
DIGEST = re.compile(r"^# digest:\s*(?P<sig>[0-9a-f]+):(?P<fp>[0-9a-f]+)\s*$")


def fingerprint(path):
    """Return the signing-key fingerprint of a template, or None when unsigned."""
    try:
        for line in reversed(path.read_text(encoding="utf-8", errors="replace").splitlines()):
            if not line.strip():
                continue
            m = DIGEST.match(line)
            return m.group("fp") if m else None
    except OSError as e:
        print(f"::warning file={path}::could not read: {e}")
    return None


def repo_root(explicit=None):
    """Resolve the repo to scan: an explicit path, else git's toplevel, else cwd."""
    if explicit:
        return pathlib.Path(explicit).resolve()
    try:
        out = subprocess.run(["git", "rev-parse", "--show-toplevel"],
                             capture_output=True, text=True, check=True).stdout.strip()
        if out:
            return pathlib.Path(out)
    except (OSError, subprocess.CalledProcessError):
        pass
    return pathlib.Path.cwd()


def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--root", help="repo root to scan (default: git toplevel, else cwd)")
    args = ap.parse_args()

    repo = repo_root(args.root)
    if not any((repo / r).is_dir() for r in ROOTS):
        print(f"::error::no {' or '.join(ROOTS)} directory under {repo} — wrong root?")
        return 1

    offenders, checked = [], 0

    for root in ROOTS:
        for path in sorted((repo / root).rglob("*.yaml")):
            checked += 1
            fp = fingerprint(path)
            if fp is not None and fp != PD_KEY:
                rel = path.relative_to(repo)
                offenders.append((rel, fp))
                print(f"::error file={rel}::signed with {fp}, expected the "
                      f"ProjectDiscovery key {PD_KEY}. Remove the '# digest:' line "
                      f"so the signing workflow can re-sign this template.")

    if offenders:
        print(f"\n{len(offenders)} of {checked} template(s) carry a foreign signing key:\n")
        for rel, fp in offenders:
            print(f"  {rel}\n    key: {fp}")
        print("\nNuclei will refuse to run these, and the signing workflow cannot "
              "repair them while the signature is present. Delete the '# digest:' "
              "line from each file:\n")
        print("  sed -i '/^# digest:/d' \\\n    " +
              " \\\n    ".join(str(rel) for rel, _ in offenders))
        return 1

    print(f"OK — all {checked} code/javascript templates are unsigned or signed "
          f"with the ProjectDiscovery key.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
