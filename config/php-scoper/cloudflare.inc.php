<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Cloudflare\\APO\\Vendor',

	'finders' => [
		Finder::create()
			->files()
			->in( 'vendor/cloudflare/cf-ip-rewrite/src' )
			->name( [ '*.php' ] ),
		Finder::create()
			->files()
			->in( 'vendor/cloudflare/cf-ip-rewrite' )
			->depth( '== 0' )
			->name( [ 'LICENSE', 'composer.json' ] ),
	],

	'patchers' => [],
];
