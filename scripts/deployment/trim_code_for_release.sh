#!/bin/bash
#$1 = repository folder name

REPOSITORY_FOLDER="$1"

if [ -z "$REPOSITORY_FOLDER" ]; then
	echo "Please provide the folder name as argument."
	exit 1
fi

rm -f $REPOSITORY_FOLDER/phpunit.xml
rm -f $REPOSITORY_FOLDER/.editorconfig
rm -rf $REPOSITORY_FOLDER/.git
rm -rf $REPOSITORY_FOLDER/src/Test/
rm -rf $REPOSITORY_FOLDER/git-hooks
rm -rf $REPOSITORY_FOLDER/compiled.map.js
rm -rf $REPOSITORY_FOLDER/scripts
rm -rf $REPOSITORY_FOLDER/vendor/bin/
rm -rf $REPOSITORY_FOLDER/vendor/squizlabs
rm -rf $REPOSITORY_FOLDER/vendor/phpunit
rm -rf $REPOSITORY_FOLDER/vendor/php-mock
rm -rf $REPOSITORY_FOLDER/vendor/johnkary 
rm -rf $REPOSITORY_FOLDER/vendor/guzzle/guzzle/tests
rm -rf $REPOSITORY_FOLDER/vendor/guzzle/guzzle/docs
rm -rf $REPOSITORY_FOLDER/vendor/phpdocumentor
rm -rf $REPOSITORY_FOLDER/vendor/webmozart
rm -rf $REPOSITORY_FOLDER/vendor/sebastian/global-state