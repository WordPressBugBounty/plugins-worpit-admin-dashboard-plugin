<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Sync extends Base {

	public function collect() :array {
		return [
			'capabilities'    => ( new Capabilities() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-info'  => ( new WordPress() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-paths' => ( new Paths() )->setRequestParams( $this->getRequestParams() )->collect(),
		];
	}

	public function process() :ApiResponse {
		if ( \class_exists( 'DirectoryIterator', false ) ) {
			$this->cleanRollbackData();
			$this->cleanRollbackDir();
		}
		return $this->success( $this->collect() );
	}

	protected function cleanRollbackData() {
		$boundary = time() - WEEK_IN_SECONDS;
		$FS = $this->loadFS();

		foreach ( [ 'plugins', 'themes' ] as $context ) {
			$dir = path_join( $this->getRollbackBaseDir(), $context );
			if ( is_dir( $dir ) ) {
				try {
					foreach ( new \DirectoryIterator( $dir ) as $file ) {
						if ( $file->isDir() && !$file->isDot() ) {
							if ( $file->getMTime() < $boundary ) {
								$FS->deleteDir( $file->getPathname() );
							}
						}
					}
				}
				catch ( \Exception $oE ) { //  UnexpectedValueException, RuntimeException, Exception
					continue;
				}
			}
		}
	}

	protected function cleanRollbackDir() {
		$FS = $this->loadFS();
		try {
			foreach ( new \DirectoryIterator( $this->getRollbackBaseDir() ) as $file ) {
				if ( !$file->isDot() ) {

					if ( !$file->isDir() ) {
						$FS->deleteFile( $file->getPathname() );
					}
					elseif ( !in_array( $file->getFilename(), [ 'plugins', 'themes' ] ) ) {
						$FS->deleteDir( $file->getPathname() );
					}
				}
			}
		}
		catch ( \Exception $e ) {
			//  UnexpectedValueException, RuntimeException, Exception
		}
	}

	protected function getRollbackBaseDir() :string {
		return path_join( WP_CONTENT_DIR, 'icwp/rollback/' );
	}
}