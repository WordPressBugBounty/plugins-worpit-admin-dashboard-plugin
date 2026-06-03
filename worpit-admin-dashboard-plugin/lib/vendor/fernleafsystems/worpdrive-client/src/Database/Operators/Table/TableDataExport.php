<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Operators\Table;

use FernleafSystems\WorpdriveClient\Database\Operators\{
	Config,
	SqlDumpIdentifierEscaper
};
use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class TableDataExport {

	private Config $cfg;

	private string $table;

	private array $content = [];

	private ?int $totalDataRows = null;

	private ?int $previousDataRows = null;

	private ?array $mostRecentRow = null;

	public function __construct( string $table, Config $cfg ) {
		$this->cfg = $cfg;
		$this->table = $table;
	}

	public function getContent( bool $flush = false ) :array {
		$content = $this->content;
		if ( $flush ) {
			$this->content = [];
		}
		return $content;
	}

	public function getTotalDataRowsCount() :?int {
		return $this->totalDataRows;
	}

	public function getPreviousDataRowsCount() :?int {
		return $this->previousDataRows;
	}

	public function getMostRecentRow() :?array {
		return $this->mostRecentRow;
	}

	/**
	 * @throws \Exception
	 */
	public function buildDataRows( array $where = [], string $orderBy = '', int $limit = 0, int $offset = 0, array $additionalSelectColumns = [] ) :void {
		$DB = WorpdriveRuntime::host()->database();
		$idEscaper = new SqlDumpIdentifierEscaper();
		$tableHelper = new TableHelper( $this->table );
		$columns = $tableHelper->getInsertableColumns();
		$selectColumns = \array_values( \array_unique( \array_merge( \array_keys( $columns ), $additionalSelectColumns ) ) );

		$rows = $DB->selectCustom( sprintf(
			"SELECT %s FROM %s %s %s %s;",
			\implode( ', ', $idEscaper->escapeAll( $selectColumns ) ),
			$idEscaper->escape( $this->table ),
			empty( $where ) ? '' : sprintf( ' WHERE %s', \implode( ' AND ', $where ) ),
			$orderBy,
			empty( $limit ) ? '' : sprintf( ' LIMIT %s OFFSET %s', $limit, $offset )
		) );
		if ( !\is_array( $rows ) ) {
			throw new \Exception( \sprintf( 'Database query failed for table: %s', $this->table ) );
		}

		$this->previousDataRows = \count( $rows );
		$this->totalDataRows = ( $this->totalDataRows ?? 0 ) + $this->previousDataRows;
		if ( empty( $rows ) ) {
			$this->mostRecentRow = null;
			return;
		}

		$this->mostRecentRow = \array_pop( $rows );
		$rows[] = $this->mostRecentRow;

		$this->addContent( ( new TableRowsSqlBuilder( $this->cfg ) )->buildInsertLines( $this->table, $rows, $columns ) );

		if ( $this->cfg->has( 'single-transaction' ) ) {
			if ( $DB->doSql( 'COMMIT;' ) === false ) {
				throw new \Exception( 'Failed to commit transaction' );
			}
		}
	}

	public function addLine( string $line ) {
		$this->addContent( [ $line ] );
	}

	public function addContent( array $lines ) {
		$this->content = \array_merge( $this->content, $lines );
	}
}
