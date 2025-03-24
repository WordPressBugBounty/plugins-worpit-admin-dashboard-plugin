<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Time;

class WorldTimeApi {

	private static int $current;

	/**
	 * @throws \Exception
	 */
	public function current() :int {
		return self::$current ??= $this->req();
	}

	/**
	 * @throws \Exception
	 */
	private function req() :int {
		$raw = wp_remote_retrieve_body( wp_remote_get( 'https://api.aptoweb.com/api/v1/time' ) );
		if ( empty( $raw ) ) {
			throw new \Exception( 'Request to World Clock Api Failed' );
		}
		$dec = \json_decode( $raw, true );
		if ( empty( $dec ) ) {
			throw new \Exception( 'Failed to decode World Clock Api response' );
		}
		return (int)$dec[ 'current' ][ 'seconds' ];
	}

	/**
	 * @throws \Exception
	 */
	public function diffServerWithReal() :int {
		return (int)\abs( \time() - $this->current() );
	}
}