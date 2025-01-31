<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

class Download extends Base {

	/**
	 * @throws \Exception
	 */
	public function byID( string $id ) {
		$id = sanitize_key( $id ); // no funky biz
		if ( !empty( $id ) ) {
			$file = \realpath( path_join( $this->getZipsDir(), $id.'.zip' ) );
			if ( !empty( $file ) && $this->loadFS()->exists( $file ) ) {
				$this->sendFile( $file );
				$this->loadFS()->deleteFile( $file );
			}
		}
		die();
	}

	private function sendFile( string $file ) {
		\header( "Pragma: public" );
		\header( "Expires: 0" );
		\header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		\header( "Cache-Control: public" );
		\header( "Content-Description: File Transfer" );
		\header( "Content-type: application/octet-stream" );
		\header( 'Content-Disposition: attachment; filename="'.\basename( $file ).'"' );
		\header( "Content-Transfer-Encoding: binary" );
		\header( "Content-Length: ".\filesize( $file ) );
		@\readfile( $file );
	}
}
