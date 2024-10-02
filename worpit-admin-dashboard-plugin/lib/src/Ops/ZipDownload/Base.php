<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Traits\PluginControllerConsumer;

class Base extends \ICWP_APP_Foundation {

	use PluginControllerConsumer;

	/**
	 * @throws \Exception
	 */
	protected function getZipsDir( bool $makeDir = true ) :string {
		$FS = FileSystem::Instance();
		$tmp = $this->getCon()->getPath_Temp();
		if ( empty( $tmp ) || !FileSystem::Instance()->isDir( $tmp ) ) {
			throw new \Exception( 'TMP dir does not exist.' );
		}
		$zipsDir = path_join( $this->getCon()->getPath_Temp(), 'zips' );
		if ( $makeDir && !$FS->mkdir( $zipsDir ) ) {
			throw new \Exception( sprintf( 'Could not create temp dir to store zip: %s', $zipsDir ) );
		}
		return $zipsDir;
	}
}
