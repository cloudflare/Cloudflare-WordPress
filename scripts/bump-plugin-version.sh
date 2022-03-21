#!/usr/bin/env bash

if [ -z "$1" ]; then
    echo "VERSION not provided."
    exit 1
fi

VERSION=$1

echo "Preparing release: $VERSION"

echo "==> Updating config.json..."
# config.json
NEW_CONFIG_JSON=$(cat config.json | jq ".version |= \"$VERSION\""); echo $NEW_CONFIG_JSON | jq . > config.json
echo "==> Complete ✅"

echo "==> Updating composer.json..."
# composer.json
NEW_COMPOSER_JSON=$(cat composer.json | jq ".version |= \"$VERSION\""); echo $NEW_COMPOSER_JSON | jq . > composer.json
echo "==> Complete ✅"

echo "==> Updating readme.txt..."
# readme.txt
sed -i '' "s/Stable tag:.*/Stable tag: $VERSION/g" readme.txt
echo "==> Complete ✅"

echo "==> Updating cloudflare.php..."
# cloudflare.php
sed -i '' "s/Version:.*/Version: $VERSION/g" cloudflare.php
echo "==> Complete ✅"
echo
echo "Release preparation complete! Don't forget to:"
echo "- Add a CHANGELOG entry to readme.txt"
echo "- \`composer update --no-dev\` to update the content-hash attribute"
echo "- Commit all the changes and push!"
