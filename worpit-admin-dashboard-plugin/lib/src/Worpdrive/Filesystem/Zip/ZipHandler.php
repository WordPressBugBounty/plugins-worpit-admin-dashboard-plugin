<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Zip;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class ZipHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\BaseFsHandler {

	private array $paths;

	private ?string $targetZIP = null;

	/**
	 * @throws \Exception
	 */
	public function __construct( string $uuid, int $stopAtTS, string $dir, array $filePaths ) {
		parent::__construct( $uuid, $stopAtTS, $dir );
		$this->paths = $filePaths;

		$allPresent = true;
		foreach ( $filePaths as $filePath ) {
			if ( !\is_file( path_join( $this->dir, $filePath ) ) ) {
				$allPresent = false;
			}
		}
		if ( !$allPresent ) {
			throw new \Exception( 'Some files in the list do not exist.' );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		try {
			$this->createZip();
		}
		catch ( \Exception $e ) {
			FileSystem::Instance()->delete( $this->targetZip() );
			throw $e;
		}
		return [
			'href' => $this->zipURL(),
		];
	}

	/**
	 * @throws \Exception
	 */
	private function createZip() :void {
		$zip = new \ZipArchive();
		if ( !$zip->open( $this->targetZip(), \ZIPARCHIVE::CREATE ) ) {
			throw new \Exception( 'Failed to create new Zip file' );
		}

		foreach ( $this->paths as $path ) {
			$full = path_join( $this->dir, $path );
			if ( !\is_file( $full ) ) {
				throw new \Exception( sprintf( 'File does not exist: %s', $full ) );
			}
			$zip->addFile( $full, \ltrim( $path, '/' ) );
		}

		if ( !$zip->close() ) {
			throw new \Exception( 'Failed to write the new ZIP file' );
		}
	}

	private function targetZip() :string {
		if ( empty( $this->targetZIP ) ) {
			$this->targetZIP = path_join( $this->workingDir(), 'archive.zip' );
			if ( \is_file( $this->targetZIP ) ) {
				FileSystem::Instance()->delete( $this->targetZIP );
			}
		}
		return $this->targetZIP;
	}

	private function zipURL() :string {
		return remove_query_arg(
			'ver',
			self::con()->getPluginUrl(
				sprintf( '%s/%s', untrailingslashit( $this->baseArchivePath() ), \basename( $this->targetZip() ) )
			)
		);
	}
}