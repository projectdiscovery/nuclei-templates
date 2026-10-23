#!/bin/bash
#
# Synchronisation du fork Flawfence avec upstream (ProjectDiscovery).
#
# Certains templates existent EN DOUBLE : ProjectDiscovery publie sa propre
# version au chemin/ID canonique (ex. détection active), et l'on conserve NOTRE
# variante (ex. détection passive par version) sous un nom distinct suffixé
# « -flawfence ». Pour éviter un conflit de merge récurrent, on garde toujours
# le template upstream au chemin canonique et notre variante à part.
#
# Ce script :
#   1. rapatrie upstream ;
#   2. s'assure qu'aucune variante Flawfence n'occupe un chemin canonique
#      (sinon elle est déplacée vers son nom -flawfence) ;
#   3. fusionne upstream/main, en résolvant automatiquement les conflits connus
#      sur ces templates divergents (on garde upstream, notre version part sous
#      le nom -flawfence) ;
#   4. pousse vers origin/main si tout est propre.
#
set -uo pipefail

# ------------------------------------------------------------------------------
# Divergences assumées.
# Format par entrée : "chemin_canonical_upstream|notre_chemin_flawfence|notre_id"
# Ajouter une ligne ici à chaque nouveau template où upstream prend le dessus.
# ------------------------------------------------------------------------------
DIVERGENCES=(
  "http/cves/2026/CVE-2026-109618.yaml|http/cves/2026/CVE-2026-109618-flawfence.yaml|CVE-2026-109618-flawfence"
)

info()  { printf '\033[1;34m→ %s\033[0m\n' "$*"; }
warn()  { printf '\033[1;33m! %s\033[0m\n' "$*"; }
err()   { printf '\033[1;31m✗ %s\033[0m\n' "$*" >&2; }

# Déplace l'ID d'un template (ligne « id: ... ») vers $2.
rewrite_id() { sed -i "s|^id: .*|id: $2|" "$1"; }

# 2. Libère les chemins canoniques occupés par une variante Flawfence.
enforce_divergences() {
  local changed=0 up ours id
  for d in "${DIVERGENCES[@]}"; do
    IFS='|' read -r up ours id <<< "$d"
    if [ -f "$up" ] && grep -qi '^[[:space:]]*author:.*flawfence' "$up"; then
      info "Divergence : la variante Flawfence occupe $up → déplacement vers $ours"
      mkdir -p "$(dirname "$ours")"
      git mv -f "$up" "$ours"
      rewrite_id "$ours" "$id"
      git add "$ours"
      changed=1
    fi
  done
  if [ "$changed" -eq 1 ]; then
    git commit -m "chore(divergence): variantes Flawfence renommées, chemins upstream libérés"
  fi
}

# 3. Résout les conflits de merge sur les templates divergents : on prend la
#    version upstream au chemin canonique, la nôtre (pré-merge) part en -flawfence.
resolve_divergence_conflicts() {
  local up ours id
  for d in "${DIVERGENCES[@]}"; do
    IFS='|' read -r up ours id <<< "$d"
    if git ls-files -u -- "$up" | grep -q .; then
      info "Conflit sur $up : on garde upstream, notre version part en $ours"
      mkdir -p "$(dirname "$ours")"
      git show HEAD:"$up" > "$ours"      # notre version avant le merge
      rewrite_id "$ours" "$id"
      git checkout --theirs -- "$up"     # version upstream
      git add "$up" "$ours"
    fi
  done
}

# ------------------------------------------------------------------------------
# Déroulé
# ------------------------------------------------------------------------------
if ! git diff --quiet || ! git diff --cached --quiet; then
  err "Arbre de travail non propre. Committez ou remisez vos changements avant de synchroniser."
  exit 1
fi

info "Récupération d'upstream…"
git fetch upstream || { err "git fetch upstream a échoué."; exit 1; }

enforce_divergences

info "Fusion d'upstream/main…"
if ! git merge --no-edit upstream/main; then
  resolve_divergence_conflicts
  # S'il reste des conflits non pris en charge, on s'arrête pour résolution manuelle.
  if git ls-files -u | grep -q .; then
    err "Conflits restants à résoudre manuellement :"
    git diff --name-only --diff-filter=U >&2
    warn "Résolvez-les, puis terminez avec : git commit && git push origin main"
    exit 1
  fi
  git commit --no-edit
fi

info "Envoi vers origin/main…"
git push origin main

info "Synchronisation terminée."
