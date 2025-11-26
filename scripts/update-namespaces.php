#!/usr/bin/env php
<?php
/**
 * Updates source files to use prefixed vendor namespaces.
 * 
 * Usage: php scripts/update-namespaces.php <build-dir>
 */

if ( $argc < 2 ) {
	fwrite( STDERR, "Usage: php scripts/update-namespaces.php <build-dir>\n" );
	exit( 1 );
}

$buildDir = rtrim( $argv[1], '/' );
$prefix = 'Cloudflare\\APO\\Vendor';

if ( ! is_dir( $buildDir ) ) {
	fwrite( STDERR, "Error: Directory '{$buildDir}' does not exist.\n" );
	exit( 1 );
}

// Replacements to make in use statements
$replacements = [
	'use Psr\\Log\\' => 'use ' . $prefix . '\\Psr\\Log\\',
	'use CloudFlare\\' => 'use ' . $prefix . '\\CloudFlare\\',
	'use Symfony\\Polyfill\\' => 'use ' . $prefix . '\\Symfony\\Polyfill\\',
];

// Update cloudflare.loader.php
$loaderFile = $buildDir . '/cloudflare.loader.php';
if ( file_exists( $loaderFile ) ) {
	$content = file_get_contents( $loaderFile );
	$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	file_put_contents( $loaderFile, $content );
}

// Update all PHP files in src/
$srcDir = $buildDir . '/src';
if ( is_dir( $srcDir ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $srcDir, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		if ( $file->getExtension() === 'php' ) {
			$content = file_get_contents( $file->getPathname() );
			$newContent = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
			if ( $content !== $newContent ) {
				file_put_contents( $file->getPathname(), $newContent );
			}
		}
	}
}

echo "✅ Namespaces updated in {$buildDir}\n";
