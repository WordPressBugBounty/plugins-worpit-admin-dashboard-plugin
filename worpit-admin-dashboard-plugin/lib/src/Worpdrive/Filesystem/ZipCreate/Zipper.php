<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\ZipCreate;

class Zipper {

	private string $baseDir;

	private array $filePaths;

	private string $targetZip;

	public function __construct( string $baseDir, array $filePaths, string $targetZip ) {
		$this->baseDir = $baseDir;
		$this->filePaths = $filePaths;
		$this->targetZip = $targetZip;
	}

	/**
	 * @throws \Exception
	 */
	public function create() {
		if ( \class_exists( '\ZipArchive' ) ) {
			$this->zipArchive();
		}
		else {
			$lib = path_join( ABSPATH, 'wp-admin/includes/class-pclzip.php' );
			if ( \is_file( $lib ) ) {
				require_once( $lib );
			}
			if ( \class_exists( '\PclZip' ) ) {
				$this->pclZip();
			}
			else {
				throw new \Exception( sprintf( 'Neither "%s" nor "%s" classes are available.', '\ZipArchive', 'PclZip' ) );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	private function pclZip() :void {
		$pclZip = new \PclZip( $this->targetZip );
		$files = \array_filter(
			\array_map( fn( $path ) => path_join( $this->baseDir, $path ), $this->filePaths ),
			fn( $path ) => \is_file( $path )
		);
		if ( empty( $pclZip->create( $files, PCLZIP_OPT_REMOVE_PATH, trailingslashit( $this->baseDir ) ) ) ) {
			throw new \Exception( 'Failed to create new Zip file with PclZip: '.$pclZip->errorInfo( true ) );
		}
	}

	/**
	 * @throws \Exception
	 */
	private function zipArchive() :void {
		$zip = new \ZipArchive();
		if ( !$zip->open( $this->targetZip, \ZIPARCHIVE::CREATE ) ) {
			throw new \Exception( 'Failed to create new Zip file' );
		}
		foreach ( $this->filePaths as $path ) {
			$full = path_join( $this->baseDir, $path );
			if ( \is_file( $full ) ) {
				$zip->addFile( $full, \ltrim( $path, '/' ) );
			}
		}
		if ( !$zip->close() ) {
			throw new \Exception( 'Failed to create the new ZIP file' );
		}
	}
}