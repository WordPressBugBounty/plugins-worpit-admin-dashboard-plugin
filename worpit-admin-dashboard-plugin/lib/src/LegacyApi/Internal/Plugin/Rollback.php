<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Plugins;

class Rollback extends Base {

	use \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Common\Rollback;

	public function process() :ApiResponse {

		if ( empty( $this->getFile() ) ) {
			return $this->fail( '"plugin_file" not provided in action' );
		}

		$FS = $this->loadFS();

		$dirName = \dirname( $this->getFile() );
		$dirPath = path_join( WP_PLUGIN_DIR, $dirName );

		$rollbackSourcePath = path_join( $this->getRollbackBaseDir(), sprintf( 'plugins/%s', $dirName ) );
		if ( !$FS->isDir( $rollbackSourcePath ) || $FS->isDirEmpty( $rollbackSourcePath ) ) {
			return $this->fail( 'The Rollback directory is either empty or does not exist.' );
		}

		// empty the target directory (delete it and recreate)
		$FS->deleteDir( $dirPath );
		$FS->mkdir( $dirPath );
		copy_dir( $rollbackSourcePath, $dirPath );
		$FS->deleteDir( $rollbackSourcePath );

		return $this->success( [
			'wordpress-plugins' => ( new Plugins() )->setRequestParams( $this->getRequestParams() )->collect(),
		] );
	}
}