#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 1 ]; then
    echo "Usage: $0 short-task-name" >&2
    exit 1
fi

slug="$1"
slug="$(printf '%s' "$slug" | tr '[:upper:]' '[:lower:]' | tr -cs '[:alnum:]-_' '-')"
slug="${slug#-}"
slug="${slug%-}"

if [ -z "$slug" ]; then
    echo "short-task-name must contain at least one letter or number" >&2
    exit 1
fi

script_dir="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
harness_dir="$(CDPATH= cd -- "$script_dir/.." && pwd)"
workspace_dir="$harness_dir/_workspace"
template="$harness_dir/templates/work-log-template.md"
timestamp="$(date +%Y%m%d_%H%M)"
target="$workspace_dir/${timestamp}_${slug}.md"

mkdir -p "$workspace_dir"
cp "$template" "$target"

printf '%s\n' "$target"
