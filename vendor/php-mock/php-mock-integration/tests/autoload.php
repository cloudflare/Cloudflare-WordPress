<?php // phpcs:ignore

use PHPUnit\Runner\Version;

// Compatibility with PHPUnit 8.0+
// We need to use "magic" trait \phpmock\integration\TestCaseTrait
// and instead of setUp/tearDown method in test case
// we should have setUpCompat/tearDownCompat.
if (class_exists(Version::class)
    && version_compare(Version::id(), '8.0.0') >= 0
) {
    class_alias(\phpmock\integration\TestCaseTypeHintTrait::class, \phpmock\integration\TestCaseTrait::class);
} else {
    class_alias(\phpmock\integration\TestCaseNoTypeHintTrait::class, \phpmock\integration\TestCaseTrait::class);
}

function f0()
{
}
function f1($a)
{
}
function f2($a, $b)
{
}
