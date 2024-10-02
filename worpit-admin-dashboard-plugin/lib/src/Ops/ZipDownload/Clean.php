<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class Clean extends Base {

	public function run() {
		try {
			$dir = $this->getZipsDir( false );
			FileSystem::Instance()->isDir( $dir ) && FileSystem::Instance()->deleteDir( $dir );
		}
		catch ( \Exception $e ) {
		}
	}
}