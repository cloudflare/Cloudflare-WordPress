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

// Vendor namespaces to prefix
$replacements = [];
foreach ( [
	'Psr\\Log\\',
	'CloudFlare\\',
	'Symfony\\Polyfill\\',
] as $ns ) {
	$replacements[ $ns ] = $prefix . '\\' . $ns;
}

/**
 * Apply namespace replacements to file content.
 */
function applyReplacements( string $content, array $replacements ): string {
	return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
}

// Update cloudflare.loader.php
$loaderFile = $buildDir . '/cloudflare.loader.php';
if ( file_exists( $loaderFile ) ) {
	$content = file_get_contents( $loaderFile );
	$content = applyReplacements( $content, $replacements );
	file_put_contents( $loaderFile, $content );
}

// Update all PHP files in src/, and in tests/ when the unit suite has been
// copied next to the build (see the prepare-build-tests Composer script).
foreach ( [ 'src', 'tests' ] as $dir ) {
	$dirPath = $buildDir . '/' . $dir;
	if ( ! is_dir( $dirPath ) ) {
		continue;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dirPath, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		if ( $file->getExtension() === 'php' ) {
			$content = file_get_contents( $file->getPathname() );
			$newContent = applyReplacements( $content, $replacements );
			if ( $content !== $newContent ) {
				file_put_contents( $file->getPathname(), $newContent );
			}
		}
	}
}

echo "✅ Namespaces updated in {$buildDir}\n";
