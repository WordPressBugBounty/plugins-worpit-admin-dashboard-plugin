<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\ZipCreate;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

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
	public function create() :void {
		$this->validateFilePaths();
		$sources = $this->sourceFilesByArchivePath();
		try {
			if ( !\class_exists( '\ZipArchive' ) ) {
				throw new \Exception( 'ZipArchive not supported, falling back to PclZip' );
			}
			$this->zipArchive( $sources );
		}
		catch ( \Exception $e ) {
			$lib = path_join( ABSPATH, 'wp-admin/includes/class-pclzip.php' );
			if ( \is_file( $lib ) ) {
				require_once( $lib );
			}
			if ( !\class_exists( '\PclZip' ) ) {
				throw new \Exception( sprintf( '"%s" is not available after previous \ZipArchive error "%s".', '\ZipArchive', $e->getMessage() ) );
			}
			$this->pclZip( $sources );
		}
	}

	/**
	 * @throws \Exception
	 */
	private function pclZip( array $sources ) :void {
		$this->preCreate();

		$pclZip = new \PclZip( $this->targetZip );
		$files = [];
		foreach ( $sources as $archivePath => $sourcePath ) {
			$files[] = [
				PCLZIP_ATT_FILE_NAME          => $sourcePath,
				PCLZIP_ATT_FILE_NEW_FULL_NAME => (string)$archivePath,
			];
		}

		$result = $pclZip->create( $files );
		if ( empty( $result ) ) {
			throw new \Exception( 'Failed to create new Zip file with PclZip: '.$pclZip->errorInfo( true ) );
		}
		$entries = $pclZip->listContent();
		if ( !\is_array( $entries ) ) {
			throw new \Exception( 'Failed to list PclZip archive contents: '.$pclZip->errorInfo( true ) );
		}
		$this->assertPclZipEntriesCreated( \array_keys( $sources ), $entries );
	}

	/**
	 * @throws \Exception
	 */
	private function zipArchive( array $sources ) :void {
		$this->preCreate();

		$zip = new \ZipArchive();
		$openResult = $zip->open( $this->targetZip, \ZIPARCHIVE::CREATE );
		if ( $openResult !== true ) {
			throw new \Exception( sprintf( 'Failed to create new Zip file: %s', \is_int( $openResult ) ? (string)$openResult : 'unknown error' ) );
		}
		foreach ( $sources as $archivePath => $sourcePath ) {
			if ( !$zip->addFile( $sourcePath, (string)$archivePath ) ) {
				$zip->close();
				throw new \Exception( sprintf( 'Failed to add requested file to ZIP: %s', $archivePath ) );
			}
		}
		if ( !$zip->close() ) {
			throw new \Exception( sprintf( 'Failed to write the new ZIP file: %s', $zip->getStatusString() ) );
		}
		$this->assertZipArchiveEntriesCreated( \array_keys( $sources ) );
	}

	private function preCreate() :void {
		if ( WorpdriveRuntime::host()->filesystem()->isFile( $this->targetZip ) ) {
			WorpdriveRuntime::host()->filesystem()->deleteFile( $this->targetZip );
		}
	}

	private function validateFilePaths() :void {
		$guard = new RelativeZipPathGuard();
		foreach ( $this->filePaths as $path ) {
			$guard->assertValid( $path );
		}
	}

	private function sourceFilesByArchivePath() :array {
		$sources = [];
		$missing = [];
		foreach ( $this->filePaths as $path ) {
			$archivePath = \ltrim( $path, '/' );
			$sourcePath = path_join( $this->baseDir, $path );
			if ( \is_file( $sourcePath ) ) {
				$sources[ $archivePath ] = $sourcePath;
			}
			elseif ( $path === 'wp-config.php' && !empty( \dirname( $this->baseDir ) ) ) {
				$maybeWpCfg = path_join( \dirname( $this->baseDir ), 'wp-config.php' );
				if ( \is_file( $maybeWpCfg ) ) {
					$sources[ 'wp-config.php' ] = $maybeWpCfg;
				}
				else {
					$missing[] = $path;
				}
			}
			else {
				$missing[] = $path;
			}
		}

		if ( !empty( $missing ) ) {
			throw new \Exception( sprintf( 'Requested files missing for ZIP: "%s"', \implode( '", "', $missing ) ) );
		}
		if ( empty( $sources ) ) {
			throw new \Exception( 'No requested files available for ZIP.' );
		}
		return $sources;
	}

	private function assertZipArchiveEntriesCreated( array $expectedEntries ) :void {
		$zip = new \ZipArchive();
		if ( $zip->open( $this->targetZip ) !== true ) {
			throw new \Exception( sprintf( 'Failed to reopen ZIP for verification: %s', $this->targetZip ) );
		}

		$entries = [];
		for ( $i = 0 ; $i < $zip->numFiles ; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( \is_string( $name ) ) {
				$entries[] = $this->normaliseArchivePath( $name );
			}
		}
		$zip->close();

		$this->assertExpectedEntriesWereCreated( $expectedEntries, $entries );
	}

	private function assertPclZipEntriesCreated( array $expectedEntries, array $createdEntries ) :void {
		$entries = [];
		foreach ( $createdEntries as $createdEntry ) {
			if ( !\is_array( $createdEntry ) || ( isset( $createdEntry[ 'status' ] ) && $createdEntry[ 'status' ] !== 'ok' ) ) {
				continue;
			}
			$name = $createdEntry[ 'stored_filename' ] ?? $createdEntry[ 'filename' ] ?? null;
			if ( \is_string( $name ) ) {
				$entries[] = $this->normaliseArchivePath( $name );
			}
		}

		$this->assertExpectedEntriesWereCreated( $expectedEntries, $entries );
	}

	private function assertExpectedEntriesWereCreated( array $expectedEntries, array $actualEntries ) :void {
		$missing = \array_diff(
			\array_map( fn( string $path ) :string => $this->normaliseArchivePath( $path ), $expectedEntries ),
			$actualEntries
		);
		if ( !empty( $missing ) ) {
			throw new \Exception( sprintf( 'Requested files missing from ZIP: "%s"', \implode( '", "', $missing ) ) );
		}
	}

	private function normaliseArchivePath( string $path ) :string {
		return \rtrim( \ltrim( $path, '/' ), '/' );
	}
}
