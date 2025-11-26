<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Cloudflare\\APO\\Vendor',

	'finders' => [
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-intl-idn' )
			->exclude( [ 'Test', 'Tests', 'test', 'tests', 'Resources' ] )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-intl-normalizer' )
			->exclude( [ 'Test', 'Tests', 'test', 'tests', 'Resources' ] )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
	],

	'patchers' => [],
];
