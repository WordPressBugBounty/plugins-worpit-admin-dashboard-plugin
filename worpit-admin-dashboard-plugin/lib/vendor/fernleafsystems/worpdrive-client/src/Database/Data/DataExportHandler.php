<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

use FernleafSystems\WorpdriveClient\Database\Operators\TableEnum;
use FernleafSystems\WorpdriveClient\Exc\TimeLimitReachedException;
use FernleafSystems\WorpdriveClient\Filesystem\ZipCreate\Zipper;
use FernleafSystems\WorpdriveClient\Utility\{
	DeletePreExistingFilesForType,
	FileNameFor
};

class DataExportHandler extends \FernleafSystems\WorpdriveClient\Database\BaseDbHandler {

	private array $tableExportMap;

	private ?string $dumpDir = null;

	private ?string $targetZIP = null;

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
		$map = new ExportMap();
		$exportSuccess = false;
		$errorPayload = null;
		$errorCodeHint = null;
		$errorPayloadBuilder = new DatabaseExportErrorPayload();
		try {
			$allowedTables = \array_keys( ( new TableEnum() )->enum() );
			$errorCodeHint = 'db_export_invalid_map';
			$map = new ExportMap( $this->tableExportMap, $allowedTables );
			$errorCodeHint = null;
			// Allow 2s for ZIP.
			( new PagedExporter( $this->dumpDir(), $map, $this->stopAtTS - 2 ) )->run();
			$exportSuccess = true;
		}
		catch ( TimeLimitReachedException $e ) {
			$exportSuccess = true;
		}
		catch ( \Exception $e ) {
			$errorPayload = $errorPayloadBuilder->build( $e, $map->status(), $errorCodeHint );
		}
		finally {
			$this->host()->filesystem()
					->putFileContent( path_join( $this->workingDir(), 'db_tracker.json' ), wp_json_encode( $map->status() ) );
		}

		if ( $exportSuccess ) {
			try {
				$this->createZip();
			}
			catch ( \Exception $e ) {
				$exportSuccess = false;
				$errorPayload = $errorPayloadBuilder->build( $e, $map->status(), 'db_export_zip_failed' );
				$this->deleteTargetZip();
			}
		}

		$response = [
			'href'             => $exportSuccess ? $this->zipURL() : '',
			'table_export_map' => $exportSuccess ? $map->status() : [],
		];

		return $errorPayload === null ? $response : \array_merge( $response, $errorPayload );
	}

	/**
	 * @throws \Exception
	 */
	private function createZip() :void {
		$items = $this->host()->filesystem()->enumItemsInDir( $this->dumpDir() );
		\natsort( $items );
		( new Zipper(
			$this->dumpDir(),
			\array_map( '\basename', $items ),
			$this->targetZip()
		) )->create();
		$this->host()->filesystem()->deleteDir( $this->dumpDir );
	}

	private function dumpDir() :string {
		if ( empty( $this->dumpDir ) ) {
			$this->dumpDir = path_join( $this->workingDir(), 'db_dump' );
			if ( \is_dir( $this->dumpDir ) ) {
				$this->host()->filesystem()->deleteDir( $this->dumpDir );
			}
			$this->host()->filesystem()->mkdir( $this->dumpDir );
		}
		return $this->dumpDir;
	}

	private function targetZip() :string {
		if ( empty( $this->targetZIP ) ) {
			( new DeletePreExistingFilesForType() )->delete( $this->workingDir(), 'db_exports_zip' );
			$this->targetZIP = path_join( $this->workingDir(), $this->host()->uniqueId( 4 ).'_'.FileNameFor::For( 'db_exports_zip' ) );
			if ( \is_file( $this->targetZIP ) ) {
				$this->host()->filesystem()->deleteFile( $this->targetZIP );
			}
		}
		return $this->targetZIP;
	}

	private function deleteTargetZip() :void {
		try {
			if ( $this->targetZIP !== null ) {
				$this->host()->filesystem()->delete( $this->targetZIP );
			}
		}
		catch ( \Throwable $e ) {
		}
	}

	private function zipURL() :string {
		return $this->host()->pluginUrlForItem(
			sprintf( '%s/%s', untrailingslashit( $this->baseArchivePath() ), \basename( $this->targetZip() ) )
		);
	}
}
