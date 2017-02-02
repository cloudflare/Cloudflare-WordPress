#!/bin/bash
#$1 = repository folder name

REPOSITORY_FOLDER="$1"

if [ -z "$REPOSITORY_FOLDER" ]; then
    echo "Please provide the folder name as argument."
    exit 1
fi

pushd "$REPOSITORY_FOLDER"

rm -f .editorconfig
rm -f .gitignore
rm -f .travis.yml
rm -f compiled.js.map
rm -f phpcs.xml
rm -f phpunit.xml
rm -rf .git/
rm -rf git-hooks/
rm -rf scripts/
rm -rf src/Test/
rm -rf vendor/bin/
rm -rf vendor/cloudflare/cf-ip-rewrite/.gitignore
rm -rf vendor/cloudflare/cf-ip-rewrite/tests/
rm -rf vendor/cloudflare/cloudflare-plugin-backend/.gitignore
rm -rf vendor/cloudflare/cloudflare-plugin-backend/src/Test/
rm -rf vendor/guzzle/
rm -rf vendor/psr/log/Psr/Log/Test/
rm -rf vendor/symfony/event-dispatcher/Tests/
rm -rf vendor/symfony/yaml/.gitignore
rm -rf vendor/symfony/yaml/Tests/

# dev packages
rm -rf vendor/doctrine/
rm -rf vendor/johnkary/
rm -rf vendor/phpdocumentor/
rm -rf vendor/php-mock/
rm -rf vendor/phpspec/
rm -rf vendor/phpunit/
rm -rf vendor/sebastian/
rm -rf vendor/simplyadmire/
rm -rf vendor/squizlabs/
rm -rf vendor/webmozart/
rm -rf vendor/wimg/

popd
