<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Operators\Table;

use FernleafSystems\WorpdriveClient\Database\Operators\SqlDumpIdentifierEscaper;
use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class TableHelper {

	private string $table;

	private ?array $columns = null;

	private bool $primaryKeyIndexColumnsLoaded = false;

	private ?array $primaryKeyIndexColumns = null;

	private bool $indexRowsLoaded = false;

	private ?array $indexRows = null;

	public function __construct( string $table ) {
		$this->table = $table;
	}

	/**
	 * @throws \Exception
	 */
	public function getAppropriatePrimaryKeyForOrdering() :?string {
		$priKey = $this->getStandardPrimaryKeyForTable();
		$theKey = ( !empty( $priKey ) && $this->hasColumn( $priKey ) ) ? $priKey : null;
		if ( empty( $theKey ) ) {
			$theKey = $this->detectPossiblePrimaryKey();
		}
		return $theKey;
	}

	/**
	 * @throws \Exception
	 */
	public function showColumns() :array {
		if ( $this->columns === null ) {
			$colResults = WorpdriveRuntime::host()->database()->selectCustom(
				sprintf( 'SHOW FULL COLUMNS FROM %s', ( new SqlDumpIdentifierEscaper() )->escape( $this->table ) )
			);
			if ( !\is_array( $colResults ) || empty( $colResults ) ) {
				throw new \Exception( 'No columns in results' );
			}
			$this->columns = [];
			foreach ( $colResults as $colResult ) {
				$this->columns[ $colResult[ 'Field' ] ] = $colResult;
			}
		}
		return $this->columns;
	}

	/**
	 * @throws \Exception
	 */
	public function getInsertableColumns() :array {
		$columns = \array_filter(
			$this->showColumns(),
			fn( array $column ) => !$this->isGeneratedColumn( $column )
		);
		if ( empty( $columns ) ) {
			throw new \Exception( 'No insertable columns in results' );
		}
		return $columns;
	}

	/**
	 * @throws \Exception
	 */
	public function insertableColumnSelectList() :string {
		return \implode(
			', ',
			( new SqlDumpIdentifierEscaper() )->escapeAll( \array_keys( $this->getInsertableColumns() ) )
		);
	}

	/**
	 * @throws \Exception
	 */
	public function hasColumn( string $column ) :bool {
		return isset( $this->showColumns()[ $column ] );
	}

	/**
	 * @throws \Exception
	 */
	public function getPrimaryKeyColumns() :array {
		$indexColumns = $this->getPrimaryKeyColumnsInIndexOrder();
		return $indexColumns ?? $this->getPrimaryKeyColumnsFromColumnMetadata();
	}

	public function getPrimaryKeyColumnsInIndexOrder() :?array {
		if ( !$this->primaryKeyIndexColumnsLoaded ) {
			$this->primaryKeyIndexColumns = $this->loadPrimaryKeyColumnsInIndexOrder();
			$this->primaryKeyIndexColumnsLoaded = true;
		}
		return $this->primaryKeyIndexColumns;
	}

	/**
	 * @return array{index:string,columns:array<int,string>}|null
	 */
	public function getUniqueIndexCursorCandidate() :?array {
		$indexRows = $this->loadIndexRows();
		if ( $indexRows === null ) {
			return null;
		}

		try {
			$tableColumns = $this->showColumns();
		}
		catch ( \Throwable $e ) {
			return null;
		}

		$groups = [];
		foreach ( $indexRows as $row ) {
			if ( !\is_array( $row ) ) {
				continue;
			}

			$keyName = $this->rowValue( $row, 'Key_name' );
			if ( !\is_string( $keyName ) || $keyName === '' || \strcasecmp( $keyName, 'PRIMARY' ) === 0 ) {
				continue;
			}
			$groups[ $keyName ][] = $row;
		}

		$candidates = [];
		foreach ( $groups as $keyName => $rows ) {
			$columns = $this->safeUniqueIndexColumns( $keyName, $rows, $tableColumns );
			if ( $columns !== null ) {
				$candidates[] = [
					'index'   => $keyName,
					'columns' => $columns,
				];
			}
		}

		if ( empty( $candidates ) ) {
			return null;
		}
		\usort(
			$candidates,
			static function ( array $left, array $right ) :int {
				$countCompare = \count( $left[ 'columns' ] ) <=> \count( $right[ 'columns' ] );
				return $countCompare !== 0 ? $countCompare : \strcmp( $left[ 'index' ], $right[ 'index' ] );
			}
		);
		return $candidates[ 0 ];
	}

	/**
	 * @throws \Exception
	 */
	private function getPrimaryKeyColumnsFromColumnMetadata() :array {
		return \array_keys( \array_filter(
			$this->showColumns(),
			fn( array $column ) => !empty( $column[ 'Key' ] ) && \strtolower( $column[ 'Key' ] ) === 'pri'
		) );
	}

	private function loadPrimaryKeyColumnsInIndexOrder() :?array {
		$indexRows = $this->loadIndexRows();
		if ( $indexRows === null ) {
			return null;
		}

		$primary = [];
		$primaryColumns = [];
		try {
			$tableColumns = $this->showColumns();
		}
		catch ( \Throwable $e ) {
			return null;
		}
		foreach ( $indexRows as $row ) {
			if ( !\is_array( $row ) ) {
				return null;
			}

			$keyName = $this->rowValue( $row, 'Key_name' );
			if ( !\is_string( $keyName ) ) {
				return null;
			}
			if ( \strcasecmp( $keyName, 'PRIMARY' ) !== 0 ) {
				continue;
			}

			$sequence = $this->rowValue( $row, 'Seq_in_index' );
			$column = $this->rowValue( $row, 'Column_name' );
			if ( \is_bool( $sequence ) || !( \is_int( $sequence ) || ( \is_string( $sequence ) && \ctype_digit( $sequence ) ) )
				 || (int)$sequence < 1 || !\is_string( $column ) || $column === ''
				 || \strpos( $column, "\0" ) !== false
				 || !isset( $tableColumns[ $column ] )
				 || isset( $primary[ (int)$sequence ] )
				 || isset( $primaryColumns[ $column ] ) ) {
				return null;
			}

			$primary[ (int)$sequence ] = $column;
			$primaryColumns[ $column ] = true;
		}

		if ( empty( $primary ) ) {
			return null;
		}
		\ksort( $primary, \SORT_NUMERIC );
		if ( \array_keys( $primary ) !== \range( 1, \count( $primary ) ) ) {
			return null;
		}
		return \array_values( $primary );
	}

	private function loadIndexRows() :?array {
		if ( !$this->indexRowsLoaded ) {
			try {
				$indexRows = WorpdriveRuntime::host()->database()->selectCustom(
					sprintf( 'SHOW INDEX FROM %s', ( new SqlDumpIdentifierEscaper() )->escape( $this->table ) )
				);
				$this->indexRows = \is_array( $indexRows ) ? $indexRows : null;
			}
			catch ( \Throwable $e ) {
				$this->indexRows = null;
			}
			$this->indexRowsLoaded = true;
		}
		return $this->indexRows;
	}

	private function safeUniqueIndexColumns( string $keyName, array $rows, array $tableColumns ) :?array {
		$columns = [];
		$columnNames = [];
		foreach ( $rows as $row ) {
			if ( !\is_array( $row ) ) {
				return null;
			}
			if ( $this->rowValue( $row, 'Key_name' ) !== $keyName ) {
				return null;
			}

			$nonUnique = $this->rowValue( $row, 'Non_unique' );
			if ( \is_bool( $nonUnique ) || !( \is_int( $nonUnique ) || ( \is_string( $nonUnique ) && \ctype_digit( $nonUnique ) ) )
				 || (int)$nonUnique !== 0 ) {
				return null;
			}

			$sequence = $this->rowValue( $row, 'Seq_in_index' );
			$column = $this->rowValue( $row, 'Column_name' );
			$subPart = $this->rowValue( $row, 'Sub_part' );
			if ( \is_bool( $sequence ) || !( \is_int( $sequence ) || ( \is_string( $sequence ) && \ctype_digit( $sequence ) ) )
				 || (int)$sequence < 1
				 || !\is_string( $column )
				 || $column === ''
				 || \strpos( $column, "\0" ) !== false
				 || ( $subPart !== null && $subPart !== '' )
				 || !isset( $tableColumns[ $column ] )
				 || isset( $columns[ (int)$sequence ] )
				 || isset( $columnNames[ $column ] )
				 || !$this->isNotNullColumn( $tableColumns[ $column ] ) ) {
				return null;
			}

			$columns[ (int)$sequence ] = $column;
			$columnNames[ $column ] = true;
		}

		if ( empty( $columns ) ) {
			return null;
		}
		\ksort( $columns, \SORT_NUMERIC );
		if ( \array_keys( $columns ) !== \range( 1, \count( $columns ) ) ) {
			return null;
		}
		return \array_values( $columns );
	}

	private function isNotNullColumn( array $column ) :bool {
		$nullable = $this->rowValue( $column, 'Null' );
		return \is_string( $nullable ) && \strcasecmp( $nullable, 'NO' ) === 0;
	}

	private function rowValue( array $row, string $key ) {
		foreach ( $row as $rowKey => $value ) {
			if ( \strcasecmp( (string)$rowKey, $key ) === 0 ) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * @throws \Exception
	 */
	public function detectPossiblePrimaryKey() :?string {
		$primaryKeyColumns = \array_filter(
			$this->showColumns(),
			fn( array $c ) => !empty( $c[ 'Key' ] ) && \strtolower( $c[ 'Key' ] ) === 'pri'
		);
		if ( \count( $primaryKeyColumns ) !== 1 ) {
			return null;
		}

		$column = (string)\key( $primaryKeyColumns );
		$c = \current( $primaryKeyColumns );
		$type = \strtolower( (string)( $c[ 'Type' ] ?? '' ) );
		$extra = \strtolower( (string)( $c[ 'Extra' ] ?? '' ) );

		return \str_contains( $extra, 'auto_increment' )
			   && \preg_match( '#^(?:tinyint|smallint|mediumint|int|bigint)\b#', $type ) === 1
			? $column
			: null;
	}

	/**
	 * @return array - length 1; key is "Create Table" or "Create View"; value is SQL table create statement.
	 * @throws \Exception
	 */
	public function showCreate() :array {
		$tableCreateSQL = WorpdriveRuntime::host()->database()
								  ->selectCustom(
									  sprintf( 'SHOW CREATE TABLE %s', ( new SqlDumpIdentifierEscaper() )->escape( $this->table ) )
								  );
		if ( !\is_array( $tableCreateSQL ) || \count( $tableCreateSQL ) !== 1 ) {
			throw new \Exception( sprintf( 'show create table failed for %s', $this->table ) );
		}
		return \current( $tableCreateSQL );
	}

	private function getStandardPrimaryKeyForTable() :?string {
		$unPrefixed = \preg_replace( sprintf( '#^%s#i', WorpdriveRuntime::host()->database()->getPrefix() ), '', $this->table, 1 );
		if ( \str_starts_with( $unPrefixed, 'icwp_wpsf_' ) ) {
			$key = 'id';
		}
		else {
			$key = ( new EnumTablePrimaryKeys() )->all()[ $unPrefixed ] ?? null;
			if ( empty( $key ) && \function_exists( 'is_multisite' ) && is_multisite()
				 && \preg_match( '#^\d+_(.+)#', $unPrefixed, $matches ) ) {
				$key = ( new EnumTablePrimaryKeys() )->wordpressStd()[ $matches[ 1 ] ] ?? null;
			}
		}
		return $key;
	}

	private function isGeneratedColumn( array $column ) :bool {
		return \preg_match(
			'#\b(?:virtual|stored)\s+generated\b#',
			\strtolower( (string)( $column[ 'Extra' ] ?? '' ) )
		) === 1;
	}
}
