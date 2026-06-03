<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities;

class PasswordGenerator {

	public static function Gen(
		int $length = 6,
		bool $digits = true,
		bool $alpha = true,
		bool $special = true,
		bool $extraSpecial = false,
		bool $includeAmbiguous = false
	) :string {
		$chars = \implode( '', \array_keys( \array_filter( [
			'01234567989'                                          => $digits,
			'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' => $alpha,
			'!@#$%^&*()'                                           => $special,
			'-_ []{}<>~`+=,.;:/?|'                                 => $extraSpecial,
		] ) ) );

		if ( !$includeAmbiguous ) {
			$chars = \preg_replace( '#[iol0O1L]#', '', $chars );
		}

		$password = '';
		for ( $i = 0 ; $i < $length ; $i++ ) {
			$password .= \substr( $chars, wp_rand( 0, \strlen( $chars ) - 1 ), 1 );
		}

		return $password;
	}

	public static function Alpha( int $length = 6 ) :string {
		return self::Gen( $length, false, true, false );
	}

	public static function Num( int $length = 6 ) :string {
		return self::Gen( $length, true, false, false );
	}

	public static function Uniqid( int $length = 12 ) :string {
		return self::Gen( $length, true, true, false );
	}
}
