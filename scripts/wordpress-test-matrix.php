#!/usr/bin/env php
<?php

/**
 * Builds the GitHub Actions matrix for the integration tests.
 *
 * The plugin supports the five newest WordPress major versions (7.1, 7.0, 6.9
 * and so on). The matrix tests the latest release of each of them on the
 * lowest supported PHP version, plus the newest release on the newest PHP.
 *
 * It also warns when readme.txt's "Requires at least" is not the oldest of
 * those five majors, which happens after each new WordPress major release.
 *
 * Usage: php scripts/wordpress-test-matrix.php
 * In GitHub Actions the matrix is written to $GITHUB_OUTPUT as "matrix".
 */

const SUPPORTED_MAJORS = 5;
const LOWEST_PHP = '7.4';
const HIGHEST_PHP = '8.4';

$context = stream_context_create(array('http' => array('timeout' => 30)));
$response = file_get_contents('https://api.wordpress.org/core/stable-check/1.0/', false, $context);
$releases = is_string($response) ? json_decode($response, true) : null;
if (!is_array($releases)) {
    fwrite(STDERR, "Could not read the WordPress release list from api.wordpress.org.\n");
    exit(1);
}

// Latest release per major version, e.g. "6.9" => "6.9.9".
$latestPerMajor = array();
foreach (array_keys($releases) as $version) {
    if (!preg_match('/^(\d+\.\d+)(\.\d+)?$/', (string) $version, $matches)) {
        continue;
    }
    $major = $matches[1];
    if (!isset($latestPerMajor[$major]) || version_compare((string) $version, $latestPerMajor[$major], '>')) {
        $latestPerMajor[$major] = (string) $version;
    }
}
uksort($latestPerMajor, function ($a, $b) {
    return version_compare($b, $a);
});
$supported = array_slice($latestPerMajor, 0, SUPPORTED_MAJORS, true);

$include = array();
foreach ($supported as $version) {
    $include[] = array('wordpress' => $version, 'php' => LOWEST_PHP);
}
$include[] = array('wordpress' => reset($supported), 'php' => HIGHEST_PHP);
$matrix = json_encode(array('include' => $include));

$oldestMajor = (string) array_key_last($supported);
$readme = (string) file_get_contents(dirname(__DIR__) . '/readme.txt');
if (preg_match('/^Requires at least:\s*(\S+)/m', $readme, $matches) && $matches[1] !== $oldestMajor) {
    echo "::warning file=readme.txt::\"Requires at least\" is {$matches[1]}, but the five newest WordPress majors start at {$oldestMajor}. "
        . "Update readme.txt, the header in cloudflare.php and CLOUDFLARE_MIN_WP_VERSION.\n";
}

$githubOutput = getenv('GITHUB_OUTPUT');
if (is_string($githubOutput) && $githubOutput !== '') {
    file_put_contents($githubOutput, "matrix={$matrix}\n", FILE_APPEND);
}
echo "Supported WordPress majors: " . implode(', ', array_keys($supported)) . "\n";
echo "Matrix: {$matrix}\n";
