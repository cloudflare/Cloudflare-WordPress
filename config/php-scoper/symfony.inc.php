<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Cloudflare\\APO\\Vendor',

	'finders' => [
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-intl-idn' )
			->exclude( [ 'Test', 'Tests', 'test', 'tests' ] )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
		Finder::create()
			->files()
			->in( 'vendor/symfony/polyfill-intl-normalizer' )
			->exclude( [ 'Test', 'Tests', 'test', 'tests' ] )
			->name( [ '*.php', 'LICENSE', 'composer.json' ] ),
	],

	'patchers' => [
		static function ( string $filePath, string $prefix, string $content ): string {
			// Fix the Normalizer stub to use the fully qualified prefixed namespace
			if ( str_ends_with( $filePath, 'Resources/stubs/Normalizer.php' ) ) {
				$content = str_replace(
					'extends Symfony\\Polyfill\\Intl\\Normalizer\\Normalizer',
					'extends \\Cloudflare\\APO\\Vendor\\Symfony\\Polyfill\\Intl\\Normalizer\\Normalizer',
					$content
				);
			}

			// Fix the polyfill Normalizer class to use hardcoded constants instead of \Normalizer::
			// This breaks the circular dependency where the stub extends the polyfill class,
			// but the polyfill class references \Normalizer constants
			if ( str_ends_with( $filePath, 'polyfill-intl-normalizer/Normalizer.php' ) ) {
				$content = str_replace(
					[
						'\\Normalizer::FORM_D',
						'\\Normalizer::FORM_KD',
						'\\Normalizer::FORM_C',
						'\\Normalizer::FORM_KC',
						'\\Normalizer::NFD',
						'\\Normalizer::NFKD',
						'\\Normalizer::NFC',
						'\\Normalizer::NFKC',
					],
					[
						'4',  // FORM_D
						'8',  // FORM_KD
						'16', // FORM_C
						'32', // FORM_KC
						'4',  // NFD (same as FORM_D)
						'8',  // NFKD (same as FORM_KD)
						'16', // NFC (same as FORM_C)
						'32', // NFKC (same as FORM_KC)
					],
					$content
				);
			}

			return $content;
		},
	],
];
