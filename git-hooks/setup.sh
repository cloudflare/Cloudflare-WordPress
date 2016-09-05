#!/bin/sh

# Run php unit tests before pushing branches
cp git-hooks/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push