<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Cloudflare\\APO\\Vendor',

	'finders' => [
		Finder::create()
			->files()
			->in( 'vendor/psr/log' )
			->exclude( [ 'Test', 'Tests', 'test', 'tests' ] )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
	],

	'patchers' => [],
];
