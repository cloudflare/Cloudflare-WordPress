#!/bin/bash

# Build script for Cloudflare WordPress plugin with PHP-Scoper
# This script prefixes vendor dependencies to avoid conflicts with other plugins
#
# Prerequisites:
#   - PHP 8.1+ (required for php-scoper)
#   - Install php-scoper globally: composer global require humbug/php-scoper:^0.18

set -e

echo "🔧 Building Cloudflare WordPress Plugin with PHP-Scoper..."

# Configuration
BUILD_DIR="build"
VENDOR_PREFIXED_DIR="${BUILD_DIR}/vendor_prefixed"
OUTPUT_DIR="${BUILD_DIR}/cloudflare"

# Clean previous builds
echo "📁 Cleaning previous builds..."
rm -rf "${BUILD_DIR}"
mkdir -p "${VENDOR_PREFIXED_DIR}"
mkdir -p "${OUTPUT_DIR}"

# Install production dependencies
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --prefer-dist --no-progress --quiet

# Run PHP-Scoper to prefix vendor dependencies
composer prefix-deps

# Copy plugin source files (unmodified)
echo "📋 Copying plugin files..."
cp -r src "${OUTPUT_DIR}/"
cp -r deprecated "${OUTPUT_DIR}/"
cp cloudflare.php "${OUTPUT_DIR}/"
cp cloudflare.loader.php "${OUTPUT_DIR}/"
cp index.php "${OUTPUT_DIR}/"
cp readme.txt "${OUTPUT_DIR}/"
cp LICENSE.md "${OUTPUT_DIR}/" 2>/dev/null || true
cp config.json "${OUTPUT_DIR}/" 2>/dev/null || true
cp userConfig.js "${OUTPUT_DIR}/" 2>/dev/null || true
cp compiled.js "${OUTPUT_DIR}/" 2>/dev/null || true

# Copy non-PHP assets
cp -r assets "${OUTPUT_DIR}/" 2>/dev/null || true
cp -r fonts "${OUTPUT_DIR}/" 2>/dev/null || true
cp -r lang "${OUTPUT_DIR}/" 2>/dev/null || true
cp -r stylesheets "${OUTPUT_DIR}/" 2>/dev/null || true

# Copy prefixed vendor dependencies
echo "📦 Copying prefixed vendor dependencies..."
mkdir -p "${OUTPUT_DIR}/vendor/psr/log"
mkdir -p "${OUTPUT_DIR}/vendor/cloudflare/cf-ip-rewrite/src"
mkdir -p "${OUTPUT_DIR}/vendor/symfony"

# Copy prefixed psr/log
cp -r "${VENDOR_PREFIXED_DIR}/psr/Psr" "${OUTPUT_DIR}/vendor/psr/log/"

# Copy prefixed cloudflare/cf-ip-rewrite
cp -r "${VENDOR_PREFIXED_DIR}/cloudflare/src/CloudFlare" "${OUTPUT_DIR}/vendor/cloudflare/cf-ip-rewrite/src/"

# Copy prefixed symfony polyfills
cp -r "${VENDOR_PREFIXED_DIR}/symfony/polyfill-intl-idn" "${OUTPUT_DIR}/vendor/symfony/"
cp -r "${VENDOR_PREFIXED_DIR}/symfony/polyfill-intl-normalizer" "${OUTPUT_DIR}/vendor/symfony/"

# Generate autoloader with prefixed namespaces using Composer
echo "📝 Generating autoloader for prefixed namespaces..."
cp config/composer.build.json "${OUTPUT_DIR}/composer.json"
(cd "${OUTPUT_DIR}" && composer dump-autoload --optimize --quiet)
rm "${OUTPUT_DIR}/composer.json"

# Update source files to use prefixed vendor namespaces
echo "📝 Updating source files to use prefixed vendor namespaces..."
php scripts/update-namespaces.php "${OUTPUT_DIR}"

# Remove unsupported PHP 8 symfony/polyfill return types (for PHP 7.4 compatibility)
echo "📝 Removing PHP 8 return types from symfony polyfills..."
find "${OUTPUT_DIR}/vendor/symfony" -name "bootstrap80.php" -exec sed -i '' 's/: string|false/ /g' {} \;

# Restore dev dependencies for local development
echo "🔄 Restoring dev dependencies..."
composer install --prefer-dist --no-progress --quiet

echo "✅ Build completed successfully!"
echo "📦 Output directory: ${OUTPUT_DIR}"
