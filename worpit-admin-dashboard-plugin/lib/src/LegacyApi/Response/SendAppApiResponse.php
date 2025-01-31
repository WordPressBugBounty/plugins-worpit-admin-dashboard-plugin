<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Response;

/**
 * Output sent to App. It cannot be escaped or modified.
 */
class SendAppApiResponse {

	public function flush( array $items, bool $binary = true ) {
		$this->sendHeaders( $binary );
		die( \implode( '', \array_filter( [
			sprintf( "<icwp>%s</icwp>", $items[ 'content' ] ),
			sprintf( "<icwpencoding>%s</icwpencoding>", $items[ 'encoding' ] ),
			sprintf( "<icwpversion>%s</icwpversion>", $items[ 'version' ] ),
			empty( $items[ 'auth_key' ] ) ? null : sprintf( "<icwpauth>%s</icwpauth>", $items[ 'auth_key' ] ),
		] ) ) );
	}

	private function sendHeaders( bool $asBinary = true ) {
		\header( $asBinary ? 'Content-type: application/octet-stream' : 'Content-type: text/html' );
		\header( $asBinary ? 'Content-Transfer-Encoding: binary' : 'Content-Transfer-Encoding: quoted-printable' );
	}
}