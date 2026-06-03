<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Zip;

use FernleafSystems\WorpdriveClient\Filesystem\ZipCreate\Zipper;
use FernleafSystems\WorpdriveClient\Utility\{
	DeletePreExistingFilesForType,
	FileNameFor
};

class ZipHandler extends \FernleafSystems\WorpdriveClient\Filesystem\BaseFsHandler {

	private array $paths;

	private ?string $targetZIP = null;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $filePaths, string $dir, string $uuid, int $stopAtTS ) {
		parent::__construct( $dir, $uuid, $stopAtTS );
		$this->paths = $filePaths;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		try {
			( new Zipper( $this->dir, $this->paths, $this->targetZip() ) )->create();
		}
		catch ( \Exception $e ) {
			$this->host()->filesystem()->deleteFile( $this->targetZip() );
			throw $e;
		}
		return [
			'href' => $this->zipURL(),
		];
	}

	private function targetZip() :string {
		if ( empty( $this->targetZIP ) ) {
			( new DeletePreExistingFilesForType() )->delete( $this->workingDir(), 'files_zip' );
			$this->targetZIP = path_join( $this->workingDir(), $this->host()->uniqueId( 4 ).'_'.FileNameFor::For( 'files_zip' ) );
			if ( \is_file( $this->targetZIP ) ) {
				$this->host()->filesystem()->deleteFile( $this->targetZIP );
			}
		}
		return $this->targetZIP;
	}

	private function zipURL() :string {
		return $this->host()->pluginUrlForItem(
			sprintf( '%s/%s', untrailingslashit( $this->baseArchivePath() ), \basename( $this->targetZip() ) )
		);
	}
}
