<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class Base extends \ICWP_APP_Foundation {

	/**
	 * @throws \Exception
	 */
	protected function getZipsDir( bool $makeDir = true ) :string {
		$tmp = self::con()->getPath_Temp();
		if ( empty( $tmp ) || !FileSystem::Instance()->isDir( $tmp ) ) {
			throw new \Exception( 'TMP dir does not exist.' );
		}
		$zipsDir = path_join( self::con()->getPath_Temp(), 'zips' );
		if ( $makeDir && !FileSystem::Instance()->mkdir( $zipsDir ) ) {
			throw new \Exception( sprintf( 'Could not create temp dir to store zip: %s', esc_html( $zipsDir ) ) );
		}
		return $zipsDir;
	}
}