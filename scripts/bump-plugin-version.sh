#!/usr/bin/env bash

# Sets the plugin version in config.json, composer.json, readme.txt and
# cloudflare.php. Works from any directory and with both bash and sh.
#
# Usage: scripts/bump-plugin-version.sh 4.14.5

set -euo pipefail

if [ -z "${1:-}" ]; then
    echo "VERSION not provided."
    exit 1
fi

VERSION=$1

if ! echo "$VERSION" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$'; then
    echo "VERSION must look like 4.14.5, got: $VERSION"
    exit 1
fi

# The files are relative to the repository root.
cd "$(dirname "$0")/.."

trap 'rm -f config.json.tmp composer.json.tmp readme.txt.bak cloudflare.php.bak' EXIT

# jq writes to a temporary file first, so a failure leaves the original intact.
# --indent keeps the file's own indentation.
set_json_version() {
    jq --indent "$2" --arg version "$VERSION" '.version = $version' "$1" > "$1.tmp"
    mv "$1.tmp" "$1"
}

echo "Preparing release: $VERSION"

echo "==> Updating config.json..."
set_json_version config.json 2
echo "==> Complete ✅"

echo "==> Updating composer.json..."
set_json_version composer.json 4
echo "==> Complete ✅"

# -i.bak works with both BSD (macOS) and GNU sed.
echo "==> Updating readme.txt..."
sed -i.bak "s/Stable tag:.*/Stable tag: $VERSION/g" readme.txt
echo "==> Complete ✅"

echo "==> Updating cloudflare.php..."
sed -i.bak "s/Version:.*/Version: $VERSION/g" cloudflare.php
echo "==> Complete ✅"
echo
echo "Release preparation complete! Don't forget to:"
echo "- Add a CHANGELOG entry to readme.txt"
echo "- \`composer update --lock\` to update the content-hash in composer.lock"
echo "- Commit all the changes and push!"
