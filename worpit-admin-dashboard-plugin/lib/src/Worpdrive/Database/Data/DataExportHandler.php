<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Data;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;

class DataExportHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\BaseDbHandler {

	private array $tableExportMap;

	private ?string $dumpDir = null;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $tableExportMap, string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS );
		$this->tableExportMap = $tableExportMap;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$track = new ExportTracker( $this->tableExportMap );
		try {
			( new PagedExporter(
				$this->dumpDir(),
				1000,
				$track,
				$this->stopAtTS - 2 // Allow 2s for ZIP.
			) )->run();
			$exportSuccess = true;
		}
		catch ( TimeLimitReachedException $e ) {
			$exportSuccess = true;
		}
		catch ( \Exception $e ) {
			$exportSuccess = false;
		}
		finally {
			FileSystem::Instance()
					  ->putFileContents( path_join( $this->workingDir(), 'db_tracker.json' ), wp_json_encode( $track->status() ) );
		}

		if ( $exportSuccess ) {
			$this->createZip();
		}

		return [
			'href'             => $exportSuccess ? $this->zipURL() : '',
			'table_export_map' => $track->status(),
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
		$items = FileSystem::Instance()->enumItemsInDir( $this->dumpDir() );
		\natsort( $items );
		\array_map( fn( string $dbFile ) => $zip->addFile( $dbFile, \basename( $dbFile ) ), $items );
		if ( !$zip->close() ) {
			throw new \Exception( 'Failed to write the new DB Export ZIP file' );
		}
		FileSystem::Instance()->delete( $this->dumpDir );
	}

	private function dumpDir() :string {
		if ( empty( $this->dumpDir ) ) {
			$this->dumpDir = path_join( $this->workingDir(), 'db_dump' );
			if ( \is_dir( $this->dumpDir ) ) {
				FileSystem::Instance()->delete( $this->dumpDir );
			}
			FileSystem::Instance()->mkdir( $this->dumpDir );
		}
		return $this->dumpDir;
	}

	private function targetZip() :string {
		if ( empty( $this->targetZIP ) ) {
			$this->targetZIP = path_join( $this->workingDir(), 'db_dump.zip' );
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