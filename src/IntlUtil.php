<?php

namespace Cloudflare\APO;

use Symfony\Polyfill\Intl\Idn as p;

if ( ! defined( 'IDNA_DEFAULT' ) ) {
	define( 'IDNA_DEFAULT', 0 );
}

if ( ! defined( 'INTL_IDNA_VARIANT_UTS46' ) ) {
	define( 'INTL_IDNA_VARIANT_UTS46', 1 );
}

/**
 * Helper class for environments that do not have the intl extension installed.
 * When the intl extension is no installed, the functions will use the symfony/polyfill-intl-idn package.
 */
class IntlUtil {

	/**
	 * Wrapper for idn_to_ascii
	 * 
	 * @param mixed $domain
	 * @param mixed $flags
	 * @param mixed $variant
	 * @param mixed $idna_info
	 * @return string|bool
	 */
	public static function idn_to_ascii( $domain, $flags = IDNA_DEFAULT, $variant = INTL_IDNA_VARIANT_UTS46, &$idna_info = null ) {
		if ( function_exists( 'idn_to_ascii' ) ) {
			return idn_to_ascii( $domain, $flags, $variant, $idna_info );
		} else {
			return p\Idn::idn_to_ascii( $domain, $flags, $variant, $idna_info );
		}
	}

	/**
	 * Wrapper for idn_to_utf8
	 * 
	 * @param mixed $domain
	 * @param mixed $flags
	 * @param mixed $variant
	 * @param mixed $idna_info
	 * @return bool|string
	 */
	public static function idn_to_utf8( $domain, $flags = IDNA_DEFAULT, $variant = INTL_IDNA_VARIANT_UTS46, &$idna_info = null ) {
		if ( function_exists( 'idn_to_utf8' ) ) {
			return idn_to_utf8( $domain, $flags, $variant, $idna_info );
		} else {
			return p\Idn::idn_to_utf8( $domain, $flags, $variant, $idna_info );
		}
	}

}