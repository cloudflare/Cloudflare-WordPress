#!/bin/bash
#
# PHP-Scoper wrapper script for prefixing vendor dependencies.
# Usage: composer prefix-deps

set -e

PREFIX="Cloudflare\\APO\\Vendor"
VENDOR_PREFIXED_DIR="build/vendor_prefixed"

# Check if php-scoper is available
if ! command -v php-scoper &> /dev/null; then
    echo "❌ php-scoper is not installed globally."
    echo "   Install it with: composer global require humbug/php-scoper:^0.18"
    echo "   Note: php-scoper requires PHP 8.1+"
    exit 1
fi

echo "🔒 Running PHP-Scoper to prefix vendor dependencies..."
mkdir -p "${VENDOR_PREFIXED_DIR}"

echo "   Scoping psr/log..."
php-scoper add-prefix --prefix="${PREFIX}" --output-dir="${VENDOR_PREFIXED_DIR}/psr" --config=config/php-scoper/psr.inc.php --force --quiet

echo "   Scoping cloudflare/cf-ip-rewrite..."
php-scoper add-prefix --prefix="${PREFIX}" --output-dir="${VENDOR_PREFIXED_DIR}/cloudflare" --config=config/php-scoper/cloudflare.inc.php --force --quiet

echo "   Scoping symfony/polyfill-*..."
php-scoper add-prefix --prefix="${PREFIX}" --output-dir="${VENDOR_PREFIXED_DIR}/symfony" --config=config/php-scoper/symfony.inc.php --force --quiet

echo "✅ Vendor dependencies prefixed successfully!"
