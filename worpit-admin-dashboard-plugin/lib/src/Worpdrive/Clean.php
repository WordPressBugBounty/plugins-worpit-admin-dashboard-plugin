<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class Clean extends BaseHandler {

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$this->deleteOtherArchivesFromWorkingDirContainer();
		$this->cleanWorkingDir();
		return [];
	}

	public function deleteOtherArchivesFromWorkingDirContainer() {
		$FS = FileSystem::Instance();
		foreach ( $FS->enumItemsInDir( \dirname( $this->workingDir() ) ) as $path ) {
			if ( \is_dir( $path )
				 && \str_starts_with( \basename( $path ), 'archive-' )
				 && \basename( $path ) !== \basename( $this->workingDir() )
			) {
				$FS->delete( $path );
			}
		}
	}

	/**
	 * This should be the final call, as any other calls to ->workingDir() will recreate that dir.
	 * @throws \Exception
	 */
	public function cleanWorkingDir() {
		FileSystem::Instance()->delete( $this->workingDir() );
	}
}