#!/usr/bin/env python3
"""
Product metadata enhancement for nuclei templates.

Fills the product identity a template is missing: `metadata.vendor`,
`metadata.product` and `classification.cpe`. Templates for a known product that
ship without these are invisible to any product-keyed lookup, so this script
resolves them from several independent sources, corroborates the answer against
the template itself, and writes only what it can stand behind. Anything it
cannot prove lands in a review report instead of being guessed into the file.

Sources, highest confidence first:

  nvd-cpe-config    the CVE's own CPE configuration, read through
                    ProjectDiscovery's `affected_products` -> vendor, product,
                    cpe. Authoritative, but NVD has deferred enrichment on most
                    recent CVEs, so it covers older templates far better.
  pd-product-index  ProjectDiscovery's index, asked which vendor it files a
                    product string under. The same CPE vocabulary NVD holds,
                    through the API this repo's other automation already uses,
                    and independent of the enrichment backlog, which makes it
                    the workhorse for anything published this year.
  nvd-cpe-dict      NVD's CPE dictionary, by keyword. Only asked about products
                    the index above could not place, so it stays rare enough
                    that its unauthenticated rate limit does not bite.
  cna-record        MITRE CVE record `containers.cna.affected[]` -> the vendor
                    and product the CNA published. Always present, never
                    normalized, and often literally "n/a", so it feeds the
                    lookups above rather than being trusted on its own.
  repo-lexicon    vendor:product learned from the templates in this repo that
                  already carry it, keyed by product-identifying tag.
  wordpress-slug  a wordpress.org/plugins/<slug> reference -> product = slug,
                  which is the convention every WordPress template here follows.
  github-repo     a github.com/<owner>/<repo> advisory or commit reference ->
                  candidate vendor:product, accepted only once another source
                  or the template's own name corroborates it.

An answer is only written when NVD backs it, or when two of these sources
agree. A single vote from the repo's own tags or from a GitHub URL is reported
as a suggestion instead, because the repo contradicts itself often enough that
one vote is not worth writing into a file: mlflow is filed under three
different vendors here. On a sample of 24 templates whose real values were
hidden, every answer this wrote named the right product, and the two it was not
sure about it left alone.

Usage:
  enhance-product-metadata.py <file.yaml> ...      resolve and write
  enhance-product-metadata.py --all                walk every template
  enhance-product-metadata.py --check <file> ...   report only, non-zero exit
  enhance-product-metadata.py --offline <file> ... repo lexicon only, no network
  enhance-product-metadata.py --report out.md      write a markdown review report
  enhance-product-metadata.py --pr-comment out.md  write a PR comment, or nothing

No API key is needed. Both APIs answer 429 when pushed, so each host gets its
own gap between requests and every response is cached on disk, which is what
lets a whole-repo backfill resume where it stopped. NVD_API_KEY is read if it
happens to be set, and lifts NVD's own limit from 5 requests per 30s to 50.
"""

import argparse
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import Counter, defaultdict
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]

# Template trees that describe a product. osint, token-spray, malware, key and
# cloud-audit templates are not about a vendor's product, so they are left alone.
PRODUCT_TREES = (
    "http/cves", "http/vulnerabilities", "http/default-logins", "http/exposed-panels",
    "http/technologies", "http/misconfiguration", "http/exposures", "http/iot",
    "http/takeovers", "http/cnvd", "network/cves", "network/detection",
    "network/misconfig", "javascript/cves", "javascript/default-logins",
    "javascript/detection", "javascript/enumeration", "javascript/misconfiguration",
    "dns/cves", "ssl/cves", "code/cves", "file/cves", "websocket/cves",
)

SKIP_TREES = (
    ".github/", "helpers/", "profiles/", "workflows/", "http/osint/",
    "http/token-spray/", "http/fuzzing/", "http/honeypot/", "http/global-matchers/",
    "http/credential-stuffing/", "file/malware/", "file/keys/", "cloud/",
    "dast/", "http/miscellaneous/",
)

# Vulnerability classes, platforms and workflow markers: present on thousands of
# templates and never identify a product.
GENERIC_TAGS = {
    "cve", "vuln", "vulnerability", "kev", "vkev", "ckev", "oast", "intrusive",
    "unauth", "auth", "authenticated", "unauthenticated", "rce", "lfi", "rfi",
    "sqli", "xss", "ssrf", "xxe", "ssti", "csrf", "lfr", "traversal", "redirect",
    "deserialization", "injection", "dos", "idor", "auth-bypass", "file-read",
    "file-write", "file-upload", "info-leak", "disclosure", "exposure", "default-login",
    "panel", "tech", "detect", "takeover", "misconfig", "edb", "packetstorm",
    "seclists", "hackerone", "huntr", "plugin", "wp-plugin", "wp-theme", "wordpress",
    "joomla", "drupal", "network", "code", "js", "py", "php", "java", "golang",
    "ruby", "dotnet", "linux", "windows", "iot", "scada", "router", "firewall",
    "vpn", "cloud", "kubernetes", "docker", "git", "sso", "saml", "oauth", "jwt",
    "ldap", "smtp", "ssh", "ftp", "smb", "snmp", "rdp", "dns", "ssl", "tls",
    "login", "logs", "backup", "config", "debug", "install", "installer", "setup",
    "enum", "user-enum", "fuzz", "brute-force", "passive", "headless", "dast",
    "favicon", "sqlite", "mysql", "mssql", "postgres", "redis", "mongodb", "ai",
    "files", "file", "data", "server", "service", "console", "admin", "manager",
    "portal", "cloud-config", "env", "listing", "directory", "metadata",
    "llm", "mcp", "ml", "devops", "cicd", "api", "graphql", "rest", "soap", "xml",
    "json", "cors", "crlf", "open-redirect", "prototype-pollution", "race-condition",
    "privesc", "rce-poc", "poc", "top-200", "top-100", "critical", "high", "medium",
    "low", "info", "unknown", "status", "misc", "pd-team", "pdteam", "pdresearch",
}

NULL_VENDORS = {
    "", "n/a", "na", "unknown", "unspecified", "none", "not applicable",
    "multiple vendors", "various", "-",
}

# NVD's own vocabulary. One of these backing an answer is enough to write it;
# anything else has to be seconded by a second, independent source.
AUTHORITATIVE = {"nvd-cpe-config", "nvd-cpe-dict", "pd-product-index"}

NVD_CVE_API = "https://services.nvd.nist.gov/rest/json/cves/2.0"
NVD_CPE_API = "https://services.nvd.nist.gov/rest/json/cpes/2.0"
MITRE_CVE_API = "https://cveawg.mitre.org/api/cve"
PD_VULN_API = "https://api.projectdiscovery.io/v2/vulnerability"
PD_SEARCH_API = "https://api.projectdiscovery.io/v2/vulnerability/search"

# NVD sits behind an edge that answers 403 to agents it does not recognise, so
# this has to be a generic one.
USER_AGENT = "Mozilla/5.0 (compatible)"
CACHE_PATH = Path(os.environ.get("METADATA_CACHE", "/tmp/nt-product-metadata-cache.json"))


# --------------------------------------------------------------------------- #
# template parsing and editing (line based, so formatting and comments survive)
# --------------------------------------------------------------------------- #

class Template:
    def __init__(self, path: Path):
        self.path = path
        self.text = path.read_text(encoding="utf-8", errors="replace")
        self.lines = self.text.split("\n")

    @property
    def rel(self):
        try:
            return str(self.path.resolve().relative_to(REPO_ROOT))
        except ValueError:
            return str(self.path)

    def _info_scalar(self, key):
        m = re.search(r"^  %s:[ \t]*(.*)$" % re.escape(key), self.text, re.M)
        return m.group(1).strip() if m else None

    def _block_key(self, block, key):
        """Read a 4-space key out of a named 2-space info block."""
        inside = False
        for line in self.lines:
            if re.match(r"^  [a-zA-Z0-9_-]+:", line):
                inside = line.startswith("  %s:" % block)
                continue
            if inside:
                m = re.match(r"^    %s:[ \t]*(.*)$" % re.escape(key), line)
                if m:
                    return m.group(1).strip()
        return None

    def block_bounds(self, block):
        """(start, end) line indices of a 2-space info block, or None."""
        start = None
        for i, line in enumerate(self.lines):
            if re.match(r"^  %s:[ \t]*$" % re.escape(block), line):
                start = i
                continue
            if start is not None and re.match(r"^[^ ]|^  [a-zA-Z0-9_-]+:", line):
                return start, i
        return None

    def info_end(self):
        """Line index just past the info block, or None if there is no info."""
        start = None
        for i, line in enumerate(self.lines):
            if re.match(r"^info:", line):
                start = i
                continue
            if start is not None and re.match(r"^[^ #]", line):
                return i
        return None

    @property
    def id(self):
        m = re.search(r"^id:[ \t]*(\S+)", self.text, re.M)
        return m.group(1) if m else None

    @property
    def name(self):
        return self._info_scalar("name") or ""

    @property
    def tags(self):
        raw = self._info_scalar("tags") or ""
        return [t.strip().lower() for t in raw.split(",") if t.strip()]

    @property
    def cve_id(self):
        v = self._block_key("classification", "cve-id")
        if v:
            return v
        return self.id if self.id and re.fullmatch(r"CVE-\d{4}-\d+", self.id or "") else None

    @property
    def vendor(self):
        return self._block_key("metadata", "vendor")

    @property
    def product(self):
        return self._block_key("metadata", "product")

    @property
    def cpe(self):
        return self._block_key("classification", "cpe")

    @property
    def references(self):
        return re.findall(r"^\s+-\s+(https?://\S+)", self.text, re.M)

    # ---- writers ---------------------------------------------------------- #

    def set_metadata_pair(self, vendor, product):
        """Insert vendor/product into info.metadata, after max-request, before queries."""
        bounds = self.block_bounds("metadata")
        new = []
        if vendor and not self.vendor:
            new.append("    vendor: %s" % vendor)
        if product and not self.product:
            new.append("    product: %s" % product)
        if not new:
            return False

        if bounds is None:
            # No metadata block: open one directly above `tags:`.
            for i, line in enumerate(self.lines):
                if re.match(r"^  tags:", line):
                    self.lines[i:i] = ["  metadata:"] + new
                    return True
            # A handful of templates carry no tags line either, so fall back to
            # the end of the info block rather than dropping the values.
            end = self.info_end()
            if end is not None:
                self.lines[end:end] = ["  metadata:"] + new
                return True
            return False

        start, end = bounds
        anchor = start  # insert right after the block header by default
        for i in range(start + 1, end):
            key = re.match(r"^    ([a-zA-Z0-9_-]+):", self.lines[i])
            if key and key.group(1) in ("verified", "max-request", "max-requests"):
                anchor = i
        self.lines[anchor + 1:anchor + 1] = new
        return True

    def set_cpe(self, cpe):
        """Append cpe as the last key of info.classification."""
        if self.cpe or not cpe:
            return False
        bounds = self.block_bounds("classification")
        if bounds is None:
            return False
        start, end = bounds
        last = start
        for i in range(start + 1, end):
            if re.match(r"^    [a-zA-Z0-9_-]+:", self.lines[i]):
                last = i
        self.lines[last + 1:last + 1] = ["    cpe: %s" % cpe]
        return True

    def save(self):
        self.path.write_text("\n".join(self.lines), encoding="utf-8")
        self.text = "\n".join(self.lines)


# --------------------------------------------------------------------------- #
# normalization
# --------------------------------------------------------------------------- #

def norm_cpe_part(value):
    """Turn a human vendor or product string into CPE 2.3 shape."""
    if not value:
        return None
    v = value.strip().lower()
    if v in NULL_VENDORS:
        return None
    v = re.sub(r"\s*\(.*?\)\s*", " ", v)              # drop parentheticals
    v = re.sub(r"\bby\s+[\w .&-]+$", "", v)            # "Frontend Admin by DynamiApps"
    v = re.sub(r"\b(inc|llc|ltd|gmbh|corp|corporation|co|s\.?a\.?|b\.?v\.?)\b\.?", "", v)
    v = re.sub(r"[–—]", "-", v)
    v = re.sub(r"[^a-z0-9._\- ]+", " ", v)
    v = re.sub(r"\s+", "_", v.strip())
    v = re.sub(r"_+", "_", v).strip("_-.")
    return v or None


def product_phrase(name):
    """The product part of a template name: text before the version or the dash."""
    n = name.strip()
    n = re.split(r"\s+[-–—]\s+", n)[0]
    n = re.split(r"\s*(?:<=|>=|<|>|=)\s*[\d]", n)[0]
    n = re.sub(r"\s+\d[\d.\-a-z]*\s*$", "", n)
    n = re.sub(r"\s+(plugin|theme|extension|module)\s*$", "", n, flags=re.I)
    return n.strip()


def tokens(value):
    return {t for t in re.split(r"[^a-z0-9]+", (value or "").lower()) if len(t) > 2}


def squash(value):
    return re.sub(r"[^a-z0-9]", "", (value or "").lower())


def affinity(tpl, value):
    """How strongly the template's own name and tags point at this name.

    A boolean "does the template mention this" is not enough: every VMware
    product matches a VMware template on the vendor alone, which is how a
    vCenter template ends up labelled Cloud Foundation. Scoring the product
    words separately lets the right one win.
    """
    if not value:
        return 0
    core = re.sub(r"_(project|software|inc|llc|team)$", "", value.lower())
    words = [w for w in re.split(r"[^a-z0-9]+", core) if len(w) > 2]
    if not words:
        return 0
    name_flat = squash(tpl.name)
    tag_flat = squash(" ".join(tpl.tags))
    tagset = set(tpl.tags)
    hits = sum(1 for w in words if w in tagset or w in name_flat or w in tag_flat)
    whole = squash(core)
    if len(whole) > 3 and (whole in name_flat or whole in tag_flat):
        hits += 2
    return hits


def coverage(tpl, value):
    """What share of a product's own words the template actually mentions.

    A keyword search is a blunt instrument: searching NVD for "Composer-auth
    Json File Disclosure" happily returns IBM Tivoli Continuous Data Protection
    for Files, which shares exactly one word. Demanding that a fuzzy match
    account for most of its own name throws those away.
    """
    if not value:
        return 0.0
    core = re.sub(r"_(project|software|inc|llc|team)$", "", value.lower())
    words = [w for w in re.split(r"[^a-z0-9]+", core) if len(w) > 2]
    if not words:
        return 0.0
    name_flat = squash(tpl.name)
    tag_flat = squash(" ".join(tpl.tags))
    whole = squash(core)
    if len(whole) > 3 and (whole in name_flat or whole in tag_flat):
        return 1.0
    tagset = set(tpl.tags)
    hits = sum(1 for w in words if w in tagset or w in name_flat or w in tag_flat)
    return hits / len(words)


def lead_token(tpl):
    """The first word of the template's product phrase, lowercased."""
    words = [w for w in re.split(r"[^A-Za-z0-9]+", product_phrase(tpl.name)) if w]
    return words[0].lower() if words else ""


def names_the_product(tpl, product):
    """Does the template's leading word and the product refer to each other?

    A keyword search on a template called "Typesense Dashboard" will happily
    return somebody else's product literally named `dashboard`, and on
    "BeyondTrust Panel" it returns `pam`. Requiring the match to touch the
    first word of the name, which is the one that identifies the software,
    throws those out while keeping envoy, pypiserver and gatus.
    """
    lead = squash(lead_token(tpl))
    prod = squash(re.sub(r"_(project|software|inc|llc|team)$", "", product or ""))
    if not lead or not prod:
        return False
    return lead in prod or prod in lead


def fuzzy_ok(tpl, vendor, product):
    """Guard for the two sources that find a product by name similarity.

    "CyberPower UPS Remote Management" contains the words "remote management",
    and some unrelated vendor ships a product by exactly that name. A multi-word
    product is only believable when the template names its vendor too; a single
    distinctive token like log4j or pypiserver stands on its own.
    """
    if not names_the_product(tpl, product):
        return False
    if affinity(tpl, vendor) > 0:
        return True
    words = [w for w in re.split(r"[^a-z0-9]+", (product or "").lower()) if len(w) > 2]
    return len(words) <= 1


def corroborated(tpl, vendor, product):
    """Does the template itself back this vendor:product up at all?"""
    return affinity(tpl, product) > 0 or affinity(tpl, vendor) > 0


# --------------------------------------------------------------------------- #
# network sources, all cached on disk
# --------------------------------------------------------------------------- #

class Fetcher:
    def __init__(self, offline=False, nvd_key=None):
        self.offline = offline
        self.nvd_key = nvd_key or os.environ.get("NVD_API_KEY")
        self.cache = {}
        self.dirty = False
        self.fetches = 0
        self.failures = Counter()
        self.last_hit = {}
        if CACHE_PATH.exists():
            try:
                self.cache = json.loads(CACHE_PATH.read_text())
            except Exception:
                self.cache = {}

    def flush(self):
        if self.dirty:
            self.dirty = False
            try:
                CACHE_PATH.parent.mkdir(parents=True, exist_ok=True)
                CACHE_PATH.write_text(json.dumps(self.cache))
            except Exception:
                pass

    def _wait(self, host, nvd):
        """Both APIs answer 429 when pushed, so keep a per-host gap."""
        if nvd:
            gap = 1.0 if self.nvd_key else 6.5
        else:
            gap = 1.5
        wait = gap - (time.monotonic() - self.last_hit.get(host, 0.0))
        if wait > 0:
            time.sleep(wait)

    def _get(self, url, nvd=False):
        if self.offline:
            return None
        if url in self.cache:
            return self.cache[url]
        host = urllib.parse.urlsplit(url).netloc
        self._wait(host, nvd)
        req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
        if nvd and self.nvd_key:
            req.add_header("apiKey", self.nvd_key)
        data = None
        for attempt in range(4):
            try:
                with urllib.request.urlopen(req, timeout=30) as resp:
                    data = json.loads(resp.read().decode("utf-8", "replace"))
                break
            except urllib.error.HTTPError as exc:
                if exc.code == 404:
                    data = {}
                    break
                # 429 means back right off; anything else may be a blip.
                time.sleep((8 if exc.code == 429 else 2) * (attempt + 1))
            except Exception:
                time.sleep(2 * (attempt + 1))
            self.last_hit[host] = time.monotonic()
            self._wait(host, nvd)
        self.last_hit[host] = time.monotonic()
        if data is None:
            # A transient failure must not be remembered as "nothing there".
            self.failures[urllib.parse.urlsplit(url).netloc] += 1
            return None
        self.cache[url] = data
        self.dirty = True
        self.fetches += 1
        if self.fetches % 20 == 0:
            self.flush()
        return data

    def nvd_cve(self, cve_id):
        return self._get("%s?cveId=%s" % (NVD_CVE_API, cve_id), nvd=True) or {}

    def nvd_cpe_search(self, keyword):
        q = urllib.parse.urlencode({"keywordSearch": keyword, "resultsPerPage": 50})
        return self._get("%s?%s" % (NVD_CPE_API, q), nvd=True) or {}

    def mitre_cve(self, cve_id):
        return self._get("%s/%s" % (MITRE_CVE_API, cve_id)) or {}

    def pd_vuln(self, cve_id):
        return self._get("%s/%s" % (PD_VULN_API, cve_id)) or {}

    def pd_product_search(self, product):
        """Which CVEs ProjectDiscovery files under this exact product string."""
        q = urllib.parse.urlencode({"q": "affected_products.product:%s" % product,
                                    "limit": 3})
        return self._get("%s?%s" % (PD_SEARCH_API, q)) or {}


# --------------------------------------------------------------------------- #
# repo lexicon
# --------------------------------------------------------------------------- #

def build_lexicon(root=REPO_ROOT):
    """tag -> (vendor, product) learned from templates that already carry it.

    A tag only earns a place when one vendor:product accounts for at least 80%
    of its uses across two or more templates, which drops vulnerability-class
    and platform tags without having to enumerate them all.
    """
    tag_votes = defaultdict(Counter)
    product_index = {}
    attested = defaultdict(Counter)
    for path in root.rglob("*.yaml"):
        rel = str(path.relative_to(root))
        if rel.startswith(SKIP_TREES):
            continue
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            continue
        v = re.search(r"^    vendor:[ \t]*(\S+)", text, re.M)
        p = re.search(r"^    product:[ \t]*(\S+)", text, re.M)
        if not (v and p):
            continue
        pair = (v.group(1), p.group(1))
        product_index.setdefault(pair[1], Counter())[pair[0]] += 1
        # A vendor the repo already backs with a matching cpe line is one we can
        # reuse without inventing a cpe that may not exist.
        c = re.search(r"^    cpe:[ \t]*(\S+)", text, re.M)
        if c:
            bits = c.group(1).split(":")
            if len(bits) > 4 and (bits[3], bits[4]) == pair:
                attested[pair[1]][pair[0]] += 1
        t = re.search(r"^  tags:[ \t]*(.+)$", text, re.M)
        if not t:
            continue
        for tag in (x.strip().lower() for x in t.group(1).split(",")):
            if not tag or tag in GENERIC_TAGS or re.fullmatch(r"cve\d{4}", tag):
                continue
            tag_votes[tag][pair] += 1

    lexicon = {}
    for tag, votes in tag_votes.items():
        total = sum(votes.values())
        pair, hits = votes.most_common(1)[0]
        if total >= 2 and hits / total >= 0.8:
            lexicon[tag] = (pair, hits)

    # Where the repo has settled on one vendor for a product and proved it with
    # a cpe, that spelling wins: NVD lists xstream under both xstream and
    # xstream_project, and ten templates here already say xstream_project.
    # CPE escapes punctuation, so NVD calls Joomla `joomla\\!`. That belongs in a
    # cpe string, not in a field other tooling matches on, and this repo already
    # holds joomla, joomla\\! and joomla\\\\! as three spellings of one product.
    house_product = {}
    squashed_votes = defaultdict(Counter)
    for product, votes in product_index.items():
        squashed_votes[squash(product)][product] += sum(votes.values())
    for key, votes in squashed_votes.items():
        plain = [s for s in votes if "\\" not in s]
        if plain:
            house_product[key] = max(plain, key=lambda s: votes[s])

    house_vendor = {}
    for product, votes in attested.items():
        total = sum(votes.values())
        vendor, hits = votes.most_common(1)[0]
        if total >= 2 and hits / total >= 0.8:
            house_vendor[product] = vendor
    return lexicon, product_index, house_vendor, house_product


# --------------------------------------------------------------------------- #
# candidate generation
# --------------------------------------------------------------------------- #

class Candidate:
    __slots__ = ("vendor", "product", "cpe", "source", "weight")

    def __init__(self, vendor, product, cpe, source, weight):
        self.vendor, self.product, self.cpe = vendor, product, cpe
        self.source, self.weight = source, weight

    @property
    def pair(self):
        return (self.vendor, self.product)

    def __repr__(self):
        return "%s:%s (%s%s)" % (self.vendor, self.product, self.source,
                                 ", cpe" if self.cpe else "")


def cpe_parts(criteria):
    bits = criteria.split(":")
    if len(bits) < 6 or bits[0] != "cpe":
        return None
    return bits[3], bits[4]


def wildcard_cpe(criteria):
    """Collapse a versioned cpe to the product-level wildcard the repo uses."""
    bits = criteria.split(":")
    if len(bits) < 13:
        return None
    return ":".join(bits[:5] + ["*"] * 8)[:200]


def from_nvd_config(tpl, fetcher):
    out = []
    if not tpl.cve_id:
        return out
    data = fetcher.nvd_cve(tpl.cve_id)
    for vuln in (data.get("vulnerabilities") or []):
        for cfg in (vuln.get("cve", {}).get("configurations") or []):
            for node in cfg.get("nodes", []):
                for match in node.get("cpeMatch", []):
                    crit = match.get("criteria") or ""
                    parts = cpe_parts(crit)
                    if parts:
                        out.append(Candidate(parts[0], parts[1], wildcard_cpe(crit),
                                             "nvd-cpe-config", 100))
    return out


def from_pd_api(tpl, fetcher):
    out = []
    if not tpl.cve_id:
        return out
    data = (fetcher.pd_vuln(tpl.cve_id) or {}).get("data") or {}
    for prod in (data.get("affected_products") or []):
        vendor, product = prod.get("vendor"), prod.get("product")
        if not (vendor and product):
            continue
        cpe = None
        for crit in (prod.get("cpe") or []):
            cpe = wildcard_cpe(crit)
            if cpe:
                break
        out.append(Candidate(vendor, product, cpe, "nvd-cpe-config", 100))
    return out


def from_cna_record(tpl, fetcher):
    """CNA-published vendor/product: always there, rarely normalized."""
    out = []
    if not tpl.cve_id:
        return out
    cna = (fetcher.mitre_cve(tpl.cve_id).get("containers") or {}).get("cna") or {}
    for aff in (cna.get("affected") or []):
        for crit in (aff.get("cpes") or []):
            parts = cpe_parts(crit)
            if parts and parts[0] not in ("*", "-"):
                out.append(Candidate(parts[0], parts[1], wildcard_cpe(crit),
                                     "cna-record", 95))
        vendor = norm_cpe_part(aff.get("vendor"))
        product = norm_cpe_part(aff.get("product"))
        if vendor or product:
            out.append(Candidate(vendor, product or vendor, None, "cna-record", 55))
    return out


def from_cpe_dictionary(tpl, fetcher, phrases):
    """The official CPE vocabulary, searched by product phrase.

    Independent of NVD's CVE enrichment backlog, which is what makes it the one
    source that still answers for templates published this month. NVD allows 5
    requests per 30s unauthenticated, so the phrase loop stops as soon as a
    phrase returns something the template itself corroborates.
    """
    out = []
    seen = set()
    for phrase in phrases:
        key = (phrase or "").strip().lower()
        if len(key) < 3 or key in seen:
            continue
        seen.add(key)
        data = fetcher.nvd_cpe_search(phrase)
        if not data or not data.get("totalResults"):
            continue
        votes = Counter()
        sample = {}
        for prod in (data.get("products") or []):
            crit = prod.get("cpe", {}).get("cpeName") or ""
            if prod.get("cpe", {}).get("deprecated"):
                continue
            parts = cpe_parts(crit)
            if not parts:
                continue
            votes[parts] += 1
            sample.setdefault(parts, crit)
        hit = False
        for parts, hits in votes.most_common(6):
            if coverage(tpl, parts[1]) < 0.5:
                continue
            if not fuzzy_ok(tpl, parts[0], parts[1]):
                continue
            out.append(Candidate(parts[0], parts[1], wildcard_cpe(sample[parts]),
                                 "nvd-cpe-dict", 80 + min(hits, 10)))
            hit = True
        if hit:
            break
    return out


def product_candidates(tpl, cands):
    """CPE-shaped product strings worth asking about, best first.

    ProjectDiscovery's index matches a product string exactly, so a human name
    has to be turned into the shapes a CPE product actually takes: the leading
    phrase of the template name as one underscored token, then its contiguous
    word runs from longest to shortest, which is how "VMware vCenter Server"
    reaches `vcenter_server`.
    """
    out = []

    def add(value):
        v = norm_cpe_part(value)
        if v and len(v) > 2 and v not in out:
            out.append(v)

    # products other sources already proposed are the cheapest thing to confirm
    for c in cands:
        if c.product:
            add(c.product)

    # Longest word runs first, because a single word out of a product name is
    # usually the vendor or a generic noun: asking about "vcenter" before
    # "vcenter_server" finds a different product that happens to share a word.
    words = [w for w in re.split(r"[^A-Za-z0-9]+", product_phrase(tpl.name)) if w]
    for n in range(len(words), 1, -1):
        for i in range(0, len(words) - n + 1):
            run = words[i:i + n]
            add("_".join(run))
            if n == 2:
                # "PyPI Server" is one word in CPE: pypiserver.
                add("".join(run))

    for tag in tpl.tags:
        if tag not in GENERIC_TAGS and not re.fullmatch(r"cve\d{4}", tag):
            add(tag)
            add(tag.replace("-", "_"))

    for w in words:
        if w.lower() not in GENERIC_TAGS:
            add(w)
    return out


def from_pd_product_index(tpl, fetcher, cands, budget=8):
    """Confirm a product string against ProjectDiscovery's own index.

    This is the same CPE data NVD holds, reached through the API this repo's
    other automation already talks to, which keeps the common path off NVD
    entirely. The index answers exact product strings only, so it confirms a
    candidate and hands back the vendor rather than discovering one from prose.
    That is why product_candidates works so hard on the shapes to try.
    """
    out = []
    asked = 0
    from_others = {c.product for c in cands if c.product}
    for product in product_candidates(tpl, cands):
        proposed = product in from_others
        if asked >= budget:
            break
        if coverage(tpl, product) < 0.5:
            continue
        asked += 1
        hits = fetcher.pd_product_search(product)
        if not hits.get("total"):
            continue
        for row in (hits.get("results") or [])[:2]:
            cve = row.get("cve_id") or row.get("doc_id")
            if not cve:
                continue
            data = (fetcher.pd_vuln(cve) or {}).get("data") or {}
            for prod in (data.get("affected_products") or []):
                if prod.get("product") != product or not prod.get("vendor"):
                    continue
                if not fuzzy_ok(tpl, prod["vendor"], product):
                    continue
                cpe = None
                for crit in (prod.get("cpe") or []):
                    cpe = wildcard_cpe(crit)
                    if cpe:
                        break
                # A product another source proposed, merely confirmed to
                # exist, is not independent agreement about this template.
                label = "pd-product-confirm" if proposed else "pd-product-index"
                out.append(Candidate(prod["vendor"], product, cpe, label, 95))
            if out:
                break
        if out:
            break
    return out


def from_repo_lexicon(tpl, lexicon):
    votes = Counter()
    for tag in tpl.tags:
        if tag in GENERIC_TAGS or re.fullmatch(r"cve\d{4}", tag):
            continue
        hit = lexicon.get(tag)
        if hit:
            votes[hit[0]] += hit[1]
    return [Candidate(pair[0], pair[1], None, "repo-lexicon", 70 + min(n, 15))
            for pair, n in votes.most_common(3)]


def from_wordpress(tpl, product_index):
    out = []
    slugs = set()
    for ref in tpl.references:
        m = re.search(r"wordpress\.org/(?:plugins|themes)/([a-z0-9][a-z0-9\-]+)", ref)
        if m:
            slugs.add(m.group(1))
    for slug in slugs:
        vendor = None
        known = product_index.get(slug)
        if known:
            vendor = known.most_common(1)[0][0]
        out.append(Candidate(vendor, slug, None, "wordpress-slug", 90))
    return out


def from_github(tpl, product_index):
    """Candidates from the project's own GitHub repository.

    Only references that reach into a repository the way an advisory, patch or
    release note does are read. A bare github.com/<owner>/<repo> link is far
    more often somebody's proof-of-concept than the vendor, and taking the owner
    off one of those is how a VMware template ends up filed under the PoC
    author's handle.
    """
    out = []
    pairs = set()
    pattern = re.compile(
        r"github\.com/([A-Za-z0-9][\w.\-]*)/([A-Za-z0-9][\w.\-]*)"
        r"/(?:security/advisories|advisories|commit|commits|compare|releases|blob|pull|issues)/")
    for ref in tpl.references:
        m = pattern.search(ref)
        if not m:
            continue
        owner, repo = m.group(1), m.group(2)
        if owner.lower() in ("advisories", "github", "cvelist", "projectdiscovery",
                               "cve", "nomi-sec", "trickest"):
            continue
        pairs.add((norm_cpe_part(owner), norm_cpe_part(repo)))
    for vendor, product in pairs:
        if not (vendor and product):
            continue
        known = product_index.get(product)
        if known:
            # The repo already files this product under a vendor: trust that.
            out.append(Candidate(known.most_common(1)[0][0], product, None,
                                 "github-repo", 85))
        else:
            out.append(Candidate(vendor, product, None, "github-repo", 55))
    return out


# --------------------------------------------------------------------------- #
# resolution
# --------------------------------------------------------------------------- #

def phrases_for(tpl):
    """Search phrases for the CPE dictionary, best first."""
    out = []
    phrase = product_phrase(tpl.name)
    if phrase:
        out.append(phrase)
        words = phrase.split()
        if len(words) > 2:
            out.append(" ".join(words[:2]))
        if len(words) > 1:
            out.append(words[0])
    for tag in tpl.tags:
        if tag not in GENERIC_TAGS and not re.fullmatch(r"cve\d{4}", tag) and len(tag) > 3:
            out.append(tag.replace("-", " "))
    return out[:5]


def resolve(tpl, fetcher, lexicon, product_index):
    """Rank candidates, keep the corroborated winner, report everything else."""
    cands = []
    cands += from_repo_lexicon(tpl, lexicon)
    cands += from_wordpress(tpl, product_index)
    cands += from_github(tpl, product_index)
    if not fetcher.offline and tpl.cve_id:
        cands += from_pd_api(tpl, fetcher)
        if not any(c.source == "nvd-cpe-config" for c in cands):
            cands += from_nvd_config(tpl, fetcher)
        cands += from_cna_record(tpl, fetcher)

    if not fetcher.offline:
        # ProjectDiscovery's index carries the same CPE vocabulary and needs no
        # key, so NVD only gets asked about what it cannot answer.
        cands += from_pd_product_index(tpl, fetcher, cands)
        if not any(c.source in AUTHORITATIVE for c in cands):
            hint_phrases = phrases_for(tpl)
            for c in cands:
                if c.source == "cna-record" and c.product:
                    hint_phrases.insert(0, c.product.replace("_", " "))
            cands += from_cpe_dictionary(tpl, fetcher, hint_phrases[:5])

    # Fold candidates onto one score per vendor:product, rewarding agreement
    # between independent sources.
    scores = Counter()
    best_cpe = {}
    sources = defaultdict(set)
    # The products this CVE is actually recorded against, as opposed to products
    # that merely exist and share a word with the template's name.
    cve_products = set()
    for c in cands:
        if not (c.vendor and c.product):
            continue
        key = (c.vendor, c.product)
        scores[key] += c.weight
        sources[key].add(c.source)
        if c.source == "nvd-cpe-config" or (c.source == "cna-record" and c.cpe):
            cve_products.add(key)
        if c.cpe and key not in best_cpe:
            best_cpe[key] = c.cpe
    product_aff = {}
    for key in list(scores):
        aff = affinity(tpl, key[1])
        product_aff[key] = aff
        # Agreement between independent sources, then how well the product name
        # matches the template, then the vendor as a weak tiebreaker.
        scores[key] += 25 * (len(sources[key]) - 1) + 15 * aff
        scores[key] += 3 * affinity(tpl, key[0])

    ranked = scores.most_common()
    if cve_products:
        # When the CVE record names the affected products, the answer is one of
        # them. Otherwise a tag pointing at a sibling product plus a dictionary
        # confirming that product exists can outvote the product the template
        # names outright, which is how a Workspace ONE Access template ends up
        # labelled Workspace ONE UEM.
        narrowed = [(k, s) for k, s in ranked if k in cve_products]
        if narrowed:
            ranked = sorted(narrowed, key=lambda kv: (-(product_aff[kv[0]]), -kv[1], kv[0]))
    # Once any candidate's product is named by the template, a candidate whose
    # product is not named cannot win on its vendor alone.
    require_product = any(product_aff[k] for k, _ in ranked)
    accepted = None
    for key, score in ranked:
        if require_product and not product_aff[key]:
            continue
        if not corroborated(tpl, key[0], key[1]):
            continue
        srcs = sources[key]
        # A GitHub reference on its own is corroboration, not an answer, unless
        # the repo already files that product under a vendor.
        if srcs == {"github-repo"} and key[1] not in product_index:
            continue
        accepted = (key, score, srcs, best_cpe.get(key))
        break

    # A wordpress.org slug names the product outright, but a product with no
    # vendor beside it is half an answer, so it still needs a vendor from
    # somewhere before it counts.
    wp = next((c for c in cands if c.source == "wordpress-slug" and c.vendor), None)
    if accepted is None and wp:
        accepted = ((wp.vendor, wp.product), wp.weight, {"wordpress-slug"}, None)

    return accepted, ranked, sources, best_cpe


def enhance(tpl, fetcher, lexicon, product_index, house_vendor, house_product,
            write=True):
    needs_pair = not (tpl.vendor and tpl.product)
    needs_cpe = (not tpl.cpe and not fetcher.offline
                 and tpl.block_bounds("classification") is not None)
    if not needs_pair and not needs_cpe:
        return {"status": "complete", "file": tpl.rel}

    accepted, ranked, sources, best_cpe = resolve(tpl, fetcher, lexicon, product_index)

    # Already has vendor/product and only wants a cpe: confirm the existing pair
    # against NVD rather than taking a cpe from a pair we never agreed with.
    if not needs_pair and needs_cpe:
        key = (tpl.vendor, tpl.product)
        cpe = best_cpe.get(key)
        if not cpe and not fetcher.offline:
            for c in from_cpe_dictionary(tpl, fetcher, [tpl.product.replace("_", " ")]):
                if (c.vendor, c.product) == key and c.cpe:
                    cpe = c.cpe
                    break
        if cpe:
            if write and tpl.set_cpe(cpe):
                tpl.save()
            return {"status": "filled", "file": tpl.rel, "vendor": tpl.vendor,
                    "product": tpl.product, "cpe": cpe, "sources": ["nvd-cpe-dict"]}
        return {"status": "no-cpe", "file": tpl.rel, "name": tpl.name,
                "vendor": tpl.vendor, "product": tpl.product}

    if accepted is None:
        return {"status": "review", "file": tpl.rel, "name": tpl.name,
                "candidates": ["%s:%s [%s] %d" % (k[0], k[1], ",".join(sorted(sources[k])), s)
                               for k, s in ranked[:4]]}

    (vendor, product), score, srcs, cpe = accepted
    # Keep the cpe exactly as NVD spells it, where the escape is correct, and
    # write the plain form into the fields people and tooling read.
    plain = house_product.get(squash(product))
    if plain and plain != product:
        product = plain
        srcs = set(srcs) | {"repo-convention"}
    elif "\\" in (product or ""):
        product = product.replace("\\", "")
    if "\\" in (vendor or ""):
        vendor = vendor.replace("\\", "")
    house = house_vendor.get(product)
    if house and house != vendor:
        vendor = house
        cpe = re.sub(r"^(cpe:2\.3:[aoh]):[^:]+:", r"\1:%s:" % house, cpe) if cpe else None
        srcs = set(srcs) | {"repo-convention"}
    if not vendor or not product:
        return {"status": "review", "file": tpl.rel, "name": tpl.name,
                "candidates": ["%s:%s [%s] (incomplete)" % (vendor, product, ",".join(sorted(srcs)))]}

    # One source on its own is a guess dressed up as a fact. The repo's own
    # tags disagree with themselves often enough (mlflow is filed under three
    # different vendors) that a lone vote is not worth writing into a file.
    confident = bool(srcs & AUTHORITATIVE) or len(srcs) > 1
    if not confident:
        return {"status": "suggested", "file": tpl.rel, "name": tpl.name,
                "vendor": vendor, "product": product, "cpe": None,
                "sources": sorted(srcs), "score": score,
                "candidates": ["%s:%s [%s] %d" % (vendor, product, ",".join(sorted(srcs)), score)]}

    wrote_pair = wrote_cpe = False
    if write:
        wrote_pair = tpl.set_metadata_pair(vendor, product)
        if needs_cpe and cpe:
            wrote_cpe = tpl.set_cpe(cpe)
        if wrote_pair or wrote_cpe:
            tpl.save()
        if needs_pair and not wrote_pair:
            # Nowhere safe to put it, so do not claim it landed.
            return {"status": "review", "file": tpl.rel, "name": tpl.name,
                    "candidates": ["%s:%s [%s] (could not place in info)"
                                   % (vendor, product, ",".join(sorted(srcs)))]}
    return {"status": "filled", "file": tpl.rel, "name": tpl.name, "vendor": vendor,
            "product": product, "cpe": cpe if needs_cpe else None,
            "sources": sorted(srcs), "score": score}


# --------------------------------------------------------------------------- #
# cli
# --------------------------------------------------------------------------- #

def wants_metadata(rel):
    if rel.startswith(SKIP_TREES):
        return False
    return rel.startswith(PRODUCT_TREES)


def collect(args):
    if args.all:
        return sorted(p for p in REPO_ROOT.rglob("*.yaml")
                      if wants_metadata(str(p.relative_to(REPO_ROOT))))
    out = []
    for raw in args.files:
        p = Path(raw)
        if not p.is_absolute():
            p = REPO_ROOT / raw
        if not p.exists() or p.suffix != ".yaml":
            continue
        try:
            rel = str(p.resolve().relative_to(REPO_ROOT))
        except ValueError:
            out.append(p)
            continue
        if wants_metadata(rel):
            out.append(p)
    return out


def main():
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("files", nargs="*", help="template paths")
    ap.add_argument("--all", action="store_true", help="walk every product template")
    ap.add_argument("--check", action="store_true", help="report only, never write")
    ap.add_argument("--offline", action="store_true", help="repo lexicon only, no network")
    ap.add_argument("--report", help="write a markdown report to this path")
    ap.add_argument("--pr-comment", dest="pr_comment",
                    help="write a ready-to-post PR comment here, or nothing if there is "
                         "nothing to say")
    ap.add_argument("--limit", type=int, help="stop after this many templates")
    ap.add_argument("--quiet", action="store_true")
    args = ap.parse_args()

    if not args.files and not args.all:
        ap.error("give template paths or --all")

    targets = collect(args)
    if args.limit:
        targets = targets[:args.limit]
    if not targets:
        print("no templates needing product metadata in the given paths")
        return 0

    lexicon, product_index, house_vendor, house_product = build_lexicon()
    if not args.quiet:
        print("lexicon: %d product-identifying tags, %d known products"
              % (len(lexicon), len(product_index)), file=sys.stderr)

    fetcher = Fetcher(offline=args.offline)
    buckets = defaultdict(list)
    try:
        for path in targets:
            try:
                tpl = Template(path)
            except Exception as exc:
                buckets["error"].append({"file": str(path), "error": str(exc)})
                continue
            res = enhance(tpl, fetcher, lexicon, product_index, house_vendor,
                          house_product, write=not args.check)
            buckets[res["status"]].append(res)
            if not args.quiet and res["status"] != "complete":
                if res["status"] in ("filled", "suggested"):
                    print("  %-9s %-50s %s:%s%s  [%s]" % (
                        res["status"], Path(res["file"]).name, res["vendor"], res["product"],
                        " +cpe" if res.get("cpe") else "", ",".join(res.get("sources", []))))
                elif res["status"] == "review":
                    print("  review    %-50s %s" % (Path(res["file"]).name,
                                                    "; ".join(res.get("candidates") or ["no candidate"])))
                else:
                    print("  %-9s %s" % (res["status"], Path(res["file"]).name))
    finally:
        fetcher.flush()

    for host, count in fetcher.failures.most_common():
        print("warning: %d request(s) to %s failed, so some templates were "
              "judged on less than the full picture" % (count, host), file=sys.stderr)

    filled = len(buckets["filled"])
    suggested = len(buckets["suggested"])
    review = len(buckets["review"])
    print("\n%d scanned: %d already complete, %d %s, %d suggested, %d need review, "
          "%d cpe unresolved"
          % (len(targets), len(buckets["complete"]), filled,
             "resolvable" if args.check else "filled", suggested, review,
             len(buckets["no-cpe"])))

    if args.report:
        write_report(Path(args.report), buckets, len(targets), args)
    if args.pr_comment:
        write_pr_comment(Path(args.pr_comment), buckets)

    if args.check and (filled or suggested):
        return 1
    return 0


def pair_table(rows):
    out = ["| template | vendor | product | cpe | sources |",
           "| --- | --- | --- | --- | --- |"]
    for r in rows:
        out.append("| `%s` | `%s` | `%s` | %s | %s |" % (
            r["file"], r.get("vendor") or "", r.get("product") or "",
            "yes" if r.get("cpe") else "", ", ".join(r.get("sources") or [])))
    return out


def write_pr_comment(path, buckets):
    """The advisory comment for a pull request, or no file when all is well."""
    rows = buckets["filled"] + buckets["suggested"]
    if not rows:
        return
    lines = [
        "### Missing product metadata", "",
        "These templates name a product but ship without `metadata.vendor`, "
        "`metadata.product` or `classification.cpe`, which leaves them out of every "
        "product-keyed lookup. Values resolved from the repo and from NVD:", "",
    ]
    lines += pair_table(rows)
    lines += ["",
              "Nothing here blocks the merge: the **Product Metadata** workflow writes "
              "what it can stand behind once this lands. Setting them in the PR is still "
              "better, since you know the product and it does not.", ""]
    path.write_text("\n".join(lines), encoding="utf-8")
    print("pr comment written to %s" % path)


def write_report(path, buckets, total, args):
    lines = ["# Product metadata report", ""]
    lines.append("%d templates scanned. %d already carried it, %d resolved, %d suggested, "
                 "%d need a human."
                 % (total, len(buckets["complete"]), len(buckets["filled"]),
                    len(buckets["suggested"]), len(buckets["review"])))
    verb = "Resolvable" if args.check else "Resolved"
    if buckets["filled"]:
        lines += ["", "## %s (%d)" % (verb, len(buckets["filled"])), "",
                  "Backed by NVD, or by two independent sources that agree.", ""]
        lines += pair_table(buckets["filled"])
    if buckets["suggested"]:
        lines += ["", "## Suggested (%d)" % len(buckets["suggested"]), "",
                  "One source only, so these are not written automatically.", ""]
        lines += pair_table(buckets["suggested"])
    if buckets["review"]:
        lines += ["", "## Needs review (%d)" % len(buckets["review"]), "",
                  "| template | name | candidates |", "| --- | --- | --- |"]
        for r in buckets["review"]:
            lines.append("| `%s` | %s | %s |" % (
                r["file"], r.get("name", "").replace("|", "\\|"),
                "; ".join(r.get("candidates") or ["none"]).replace("|", "\\|")))
    if buckets["no-cpe"]:
        lines += ["", "## cpe unresolved (%d)" % len(buckets["no-cpe"]), "",
                  "vendor and product are set but NVD has no CPE entry for them yet.", ""]
        for r in buckets["no-cpe"]:
            lines.append("- `%s` (%s:%s)" % (r["file"], r.get("vendor"), r.get("product")))
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print("report written to %s" % path)


if __name__ == "__main__":
    sys.exit(main())
