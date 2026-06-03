<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

use FernleafSystems\WorpdriveClient\Database\Operators\{
	Config,
	Exporter,
	SqlDumpIdentifierEscaper,
	SqlExportSession,
	Table\TableDataExport,
	Table\TableHelper
};

class ChunkedExporter {

	/**
	 * @var resource
	 */
	private $dumpFile;

	private string $table;

	private int $startingOffset;

	private int $maxPageRows;

	private int $chunkSize;

	private ?array $cursor;

	private ?int $startingExportedRows;

	/**
	 * @throws \Exception
	 */
	public function __construct(
		$dumpFile,
		string $table,
		int $startingOffset,
		int $maxPageRows = 1000,
		int $chunkSize = 50,
		?array $cursor = null,
		?int $startingExportedRows = null
	) {
		if ( !\is_resource( $dumpFile ) ) {
			throw new \Exception( 'Dump file is not a valid resource' );
		}
		if ( $startingOffset < 0 ) {
			throw new \InvalidArgumentException( 'Starting offset must be zero or greater.' );
		}
		if ( $maxPageRows < 1 ) {
			throw new \InvalidArgumentException( 'Maximum page rows must be one or greater.' );
		}
		if ( $chunkSize < 1 ) {
			throw new \InvalidArgumentException( 'Chunk size must be one or greater.' );
		}
		if ( $startingExportedRows !== null && $startingExportedRows < 0 ) {
			throw new \InvalidArgumentException( 'Starting exported rows must be zero or greater.' );
		}
		$this->dumpFile = $dumpFile;
		$this->table = $table;
		$this->maxPageRows = $maxPageRows;
		$this->startingOffset = $startingOffset;
		$this->chunkSize = $chunkSize;
		$this->cursor = $cursor;
		$this->startingExportedRows = $startingExportedRows;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$session = new SqlExportSession();
		try {
			$session->prepareForDataExport();
			return $this->runExport();
		}
		finally {
			$session->restore();
		}
	}

	/**
	 * @throws \Exception
	 */
	private function runExport() :array {
		$cfg = ( new Config() )->applyDumpDataOptions();
		$cfg->set( 'host', \defined( 'DB_HOST' ) ? DB_HOST : '' );
		$cfg->set( 'database', \defined( 'DB_NAME' ) ? DB_NAME : '' );
		$cfg->set( 'tables', [ $this->table ] );
		$exporter = new Exporter( $cfg );

		$tableDataExp = new TableDataExport( $this->table, $cfg );
		$tableHelper = new TableHelper( $this->table );
		$primaryOrderColumn = $tableHelper->getAppropriatePrimaryKeyForOrdering();
		$idEscaper = new SqlDumpIdentifierEscaper();
		$compositeCursorHelper = new CompositePrimaryKeyCursor();
		$uniqueCursorHelper = new UniqueIndexCursor();
		$normalisedCursor = $this->normaliseCursor();
		$cursorColumns = [];
		$cursorIndex = null;
		$cursorForResponse = null;
		$mode = 'offset';
		$offsetUsesRowProgress = false;

		if ( !empty( $primaryOrderColumn ) ) {
			$mode = 'single_primary_key';
			$orderBy = sprintf( 'ORDER BY %s ASC', $idEscaper->escape( $primaryOrderColumn ) );
		}
		else {
			$indexedPrimaryColumns = $tableHelper->getPrimaryKeyColumnsInIndexOrder();
			$primaryKeyColumns = \is_array( $indexedPrimaryColumns ) ? $indexedPrimaryColumns : $tableHelper->getPrimaryKeyColumns();
			if ( \is_array( $indexedPrimaryColumns ) && \count( $indexedPrimaryColumns ) > 1
				 && ( $this->startingOffset === 0 || $compositeCursorHelper->columnsMatchCursor( $indexedPrimaryColumns, $normalisedCursor ?? [] ) ) ) {
				$mode = 'composite_primary_key';
				$cursorColumns = $indexedPrimaryColumns;
				$orderBy = $this->buildOrderBy( $cursorColumns );
				$cursorForResponse = $compositeCursorHelper->columnsMatchCursor( $cursorColumns, $normalisedCursor ?? [] ) ? $normalisedCursor : null;
			}
			else {
				$uniqueCandidate = !\is_array( $indexedPrimaryColumns ) || \count( $indexedPrimaryColumns ) <= 1
					? $tableHelper->getUniqueIndexCursorCandidate()
					: null;
				if ( \is_array( $uniqueCandidate )
					 && ( $this->startingOffset === 0 || $uniqueCursorHelper->columnsMatchCursor( $uniqueCandidate[ 'columns' ], $uniqueCandidate[ 'index' ], $normalisedCursor ?? [] ) ) ) {
					$mode = 'unique_index';
					$cursorColumns = $uniqueCandidate[ 'columns' ];
					$cursorIndex = $uniqueCandidate[ 'index' ];
					$orderBy = $this->buildOrderBy( $cursorColumns );
					$cursorForResponse = $uniqueCursorHelper->columnsMatchCursor( $cursorColumns, $cursorIndex, $normalisedCursor ?? [] ) ? $normalisedCursor : null;
				}
				else {
					$offsetUsesRowProgress = $this->shouldUseRowProgressOffsetFallback( $primaryKeyColumns, \is_array( $uniqueCandidate ) );
					$orderBy = $this->buildOrderBy(
						$offsetUsesRowProgress && \is_array( $uniqueCandidate ) ? $uniqueCandidate[ 'columns' ] : $primaryKeyColumns
					);
				}
			}
		}

		if ( $mode === 'offset' ) {
			$this->maxPageRows = (int)\max( 1, \round( 2*$this->maxPageRows/3 ) );
		}

		$pageExportComplete = false;
		$offset = $this->startingOffset;
		$isFirstLoop = true;
		$tableExportComplete = false;
		$currentOffsetForResponse = $this->startingOffset;
		$hasExportedStartingPrimaryOffset = false;
		$maxIterations = (int)\ceil( $this->maxPageRows/$this->chunkSize ) + 10;
		$iterations = 0;
		do {
			if ( ++$iterations > $maxIterations ) {
				throw new \Exception( \sprintf(
					'Export exceeded maximum iterations (%d) for table - possible infinite loop detected',
					$maxIterations
				) );
			}

			if ( $isFirstLoop ) {
				$exporter->buildHeader()
						 ->buildPreDataExport()
						 ->buildTableDataStructureStart( $this->table );
				$this->writeDump( $exporter->getContent( true ) );
				$isFirstLoop = false;
			}

			if ( $mode === 'offset' ) {
				$sqlOffset = $offsetUsesRowProgress ? $offset : $this->chunkSize*$offset;
				$tableDataExp->buildDataRows( [], $orderBy, $this->chunkSize, $sqlOffset );
				if ( $tableDataExp->getPreviousDataRowsCount() > 0 ) {
					$offset += $offsetUsesRowProgress ? $tableDataExp->getPreviousDataRowsCount() : 1;
				}
				$currentOffsetForResponse = $offset;
			}
			elseif ( $mode === 'single_primary_key' ) {
				$tableDataExp->buildDataRows(
					[
						sprintf(
							'%s %s %s',
							$idEscaper->escape( $primaryOrderColumn ),
							$offset === 0 && !$hasExportedStartingPrimaryOffset ? '>=' : '>',
							$offset
						)
					],
					$orderBy,
					$this->chunkSize
				);
				if ( !empty( $tableDataExp->getMostRecentRow() ) ) {
					$offset = (int)$tableDataExp->getMostRecentRow()[ $primaryOrderColumn ];
					$currentOffsetForResponse = $offset;
					$hasExportedStartingPrimaryOffset = true;
				}
				else {
					$currentOffsetForResponse = $offset;
				}
			}
			else {
				$where = [];
				$cursorPredicate = $mode === 'unique_index'
					? $uniqueCursorHelper->buildPredicate( $cursorColumns, (string)$cursorIndex, $cursorForResponse )
					: $compositeCursorHelper->buildPredicate( $cursorColumns, $cursorForResponse );
				if ( $cursorPredicate !== null ) {
					$where[] = $cursorPredicate;
				}

				$tableDataExp->buildDataRows( $where, $orderBy, $this->chunkSize, 0, $cursorColumns );
				$currentOffsetForResponse = $this->startingOffset + ( $tableDataExp->getTotalDataRowsCount() ?? 0 );

				if ( !empty( $tableDataExp->getMostRecentRow() ) ) {
					$nextCursor = $mode === 'unique_index'
						? $uniqueCursorHelper->cursorForRow( $cursorColumns, (string)$cursorIndex, $tableDataExp->getMostRecentRow(), $tableHelper->showColumns() )
						: $compositeCursorHelper->cursorForRow( $cursorColumns, $tableDataExp->getMostRecentRow(), $tableHelper->showColumns() );
					if ( $nextCursor === null || ( $cursorForResponse !== null && $nextCursor[ 'values' ] === $cursorForResponse[ 'values' ] ) ) {
						throw new \Exception( \sprintf( 'Export failed to make progress for table %s - cursor did not advance', $this->table ) );
					}
					$cursorForResponse = $nextCursor;
				}
			}

			if ( $tableDataExp->getPreviousDataRowsCount() === 0 ) {
				$pageExportComplete = true;
				$tableExportComplete = true;
				$this->writeDump(
					$exporter->buildTableDataStructureEnd( $this->table )
							 ->buildFooter()
							 ->getContent( true )
				);
			}
			else {
				$this->writeDump( $tableDataExp->getContent( true ) );
				if ( $tableDataExp->getTotalDataRowsCount() >= $this->maxPageRows ) {
					$pageExportComplete = true;
					$tableExportComplete = false;
					$this->writeDump(
						$exporter->buildTableDataStructureEnd( $this->table )
								 ->buildFooter()
								 ->getContent( true )
					);
				}
			}
		} while ( !$pageExportComplete && $tableDataExp->getTotalDataRowsCount() < $this->maxPageRows );

		if ( !$tableExportComplete && $currentOffsetForResponse <= $this->startingOffset ) {
			throw new \Exception( \sprintf(
				'Export failed to make progress for table %s - offset did not advance from %d',
				$this->table,
				$this->startingOffset
			) );
		}

		return [
			'table_export_complete' => $tableExportComplete,
			'current_offset'        => $currentOffsetForResponse,
			'exported_rows'         => $tableDataExp->getTotalDataRowsCount() ?? 0,
			'cursor'                => \in_array( $mode, [ 'composite_primary_key', 'unique_index' ], true ) ? $cursorForResponse : null,
		];
	}

	private function normaliseCursor() :?array {
		return ( new CompositePrimaryKeyCursor() )->normalise( $this->cursor )
			   ?? ( new UniqueIndexCursor() )->normalise( $this->cursor );
	}

	private function shouldUseRowProgressOffsetFallback( array $primaryKeyColumns, bool $hasUniqueCursorCandidate = false ) :bool {
		return ( \count( $primaryKeyColumns ) > 1 || $hasUniqueCursorCandidate )
			   && $this->startingOffset > 0
			   && $this->startingExportedRows !== null
			   && $this->startingOffset === $this->startingExportedRows;
	}

	private function buildOrderBy( array $columns ) :string {
		if ( empty( $columns ) ) {
			return '';
		}
		$idEscaper = new SqlDumpIdentifierEscaper();
		return \sprintf(
			'ORDER BY %s',
			\implode(
				', ',
				\array_map(
					fn( string $column ) => $idEscaper->escape( $column ).' ASC',
					$columns
				)
			)
		);
	}

	private function writeDump( array $raw ) :void {
		if ( \fwrite( $this->dumpFile, \implode( "\n", $raw ) ) === false ) {
			throw new \Exception( 'Failed to write Worpdrive database dump content.' );
		}
	}
}
