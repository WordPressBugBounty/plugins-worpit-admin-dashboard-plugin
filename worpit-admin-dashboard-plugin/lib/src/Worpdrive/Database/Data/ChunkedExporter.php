<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Data;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Operators\{
	Config,
	Exporter
};

class ChunkedExporter {

	/**
	 * @var resource
	 */
	private $dumpFile;

	private string $table;

	private int $startingOffset;

	private int $maxRows;

	private int $chunkSize;

	/**
	 * @throws \Exception
	 */
	public function __construct( $dumpFile, string $table, int $maxRows, int $startingOffset, int $chunkSize = 50 ) {
		if ( !\is_resource( $dumpFile ) ) {
			throw new \Exception( 'Dump file is not a valid resource' );
		}
		$this->dumpFile = $dumpFile;
		$this->table = $table;
		$this->startingOffset = $startingOffset;
		$this->maxRows = $maxRows;
		$this->chunkSize = $chunkSize;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$cfg = ( new Config() )->applyDumpDataOptions();
		$cfg->set( 'host', \defined( 'DB_HOST' ) ? DB_HOST : '' );
		$cfg->set( 'database', \defined( 'DB_NAME' ) ? DB_NAME : '' );
		$cfg->set( 'tables', [ $this->table ] );
		$exporter = new Exporter( $cfg );

		$pageExportComplete = false;
		$offset = $this->startingOffset;
		$isFirstLoop = true;
		$tableExportComplete = false;
		do {
			$cfg->set( 'where', sprintf( '1 LIMIT %s, %s', $this->chunkSize*$offset++, $this->chunkSize ) );
			if ( $isFirstLoop ) {
				$exporter->buildHeader()
						 ->buildPreDataExport()
						 ->buildTableDataStructureStart( $this->table );
			}

			$exporter->buildTableDataStructureRows( $this->table );

			if ( $exporter->getPreviousDataRowsCount() === 0 || $exporter->getTotalDataRowsCount() === $this->maxRows ) {
				$pageExportComplete = true;
				$tableExportComplete = $exporter->getPreviousDataRowsCount() === 0;
				$this->writeDump(
					$exporter->buildTableDataStructureEnd( $this->table )
							 ->buildFooter()
							 ->getContent( true )
				);
			}
			else {
				$this->writeDump( $exporter->getContent( true ) );
			}

			$isFirstLoop = false;
		} while ( !$pageExportComplete && $exporter->getTotalDataRowsCount() < $this->maxRows );

		return [
			'table_export_complete' => $tableExportComplete,
			'current_offset'        => $offset,
		];
	}

	private function writeDump( array $raw ) :void {
		\fwrite( $this->dumpFile, \implode( "\n", $raw ) );
	}
}