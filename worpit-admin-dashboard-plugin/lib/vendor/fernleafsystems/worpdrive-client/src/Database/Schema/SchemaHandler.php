<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Schema;

use FernleafSystems\WorpdriveClient\Database\Operators\{
	Config,
	Exporter,
	SqlExportSession,
	TableEnum
};
use FernleafSystems\WorpdriveClient\Filesystem\ZipCreate\Zipper;
use FernleafSystems\WorpdriveClient\Utility\FileNameFor;

class SchemaHandler extends \FernleafSystems\WorpdriveClient\Database\BaseDbHandler {

	private string $method;

	private array $tables;

	private ?string $targetZIP = null;

	/**
	 * @throws \Exception
	 */
	public function __construct( string $method, string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS );
		$this->method = $method;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$data = [
			'tables'    => $this->tables(),
			'db_prefix' => $this->host()->database()->getPrefix(),
		];
		if ( $this->method === 'zip' ) {
			$this->dumpSchemaToZip();
			$data[ 'schema_href' ] = $this->zipURL();
		}
		else { //'direct'
			$data[ 'schema_dump' ] = $this->dumpSchema();
		}
		return $data;
	}

	/**
	 * @throws \Exception
	 */
	private function dumpSchema() :array {
		$session = new SqlExportSession();
		try {
			$session->prepareForSchemaExport();
			$cfg = ( new Config() )->applyDumpSchemaOptions();
			$cfg->set( 'tables', \array_keys( $this->tables() ) );
			return ( new Exporter( $cfg ) )->export();
		}
		finally {
			$session->restore();
		}
	}

	/**
	 * @throws \Exception
	 */
	private function dumpSchemaToZip() :void {
		$toFile = $this->host()->filesystem()->putFileContent(
			$this->schemaDumpFile(),
			\implode( "\n", $this->dumpSchema()[ 'content' ] )
		);
		if ( !$toFile ) {
			throw new \Exception( 'Failed to create schema dump file.' );
		}
		$this->createZip();
	}

	private function schemaDumpFile() :string {
		return path_join( $this->workingDir(), 'db_schema_dump.sql' );
	}

	/**
	 * @throws \Exception
	 */
	private function createZip() :void {
		$items = [ $this->schemaDumpFile() ];
		( new Zipper(
			$this->workingDir(),
			\array_map( '\basename', $items ),
			$this->targetZip()
		) )->create();
		$this->host()->filesystem()->deleteFile( $this->schemaDumpFile() );
	}

	private function targetZip() :string {
		if ( empty( $this->targetZIP ) ) {
			$this->targetZIP = path_join( $this->workingDir(), FileNameFor::For( 'db_schema_zip' ) );
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

	/**
	 * @throws \Exception
	 */
	private function tables() :array {
		return $this->tables ??= ( new TableEnum() )->enum();
	}
}
