<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

/**
 * @phpstan-type CursorValue string|array{encoding:'base64',value:string}
 * @phpstan-type CompositePrimaryKeyCursorData array{strategy:'composite_primary_key',columns:array<int,string>,values:array<int,CursorValue>}
 * @phpstan-type UniqueIndexCursorData array{strategy:'unique_index',index:string,columns:array<int,string>,values:array<int,CursorValue>}
 * @phpstan-type ExportTableStatus array{offset:int,page:int,completed_at:int,exported_rows:int,max_page_rows:int,chunk_size:int,cursor?:CompositePrimaryKeyCursorData|UniqueIndexCursorData}
 */
class ExportMap {

	/**
	 * @phpstan-var array<string,ExportTableStatus>
	 */
	private array $dumpStatus;

	private ?array $allowedTables;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $dumpStatus = [], ?array $allowedTables = null ) {
		$this->allowedTables = $allowedTables === null ? null : \array_values( $allowedTables );
		$this->dumpStatus = $this->normaliseDumpStatus( $dumpStatus );
	}

	public function status() :array {
		return $this->dumpStatus;
	}

	public function updateStatus( string $table, array $status ) :void {
		$this->dumpStatus[ $table ] = $this->normaliseTableStatus( $table, $status );
	}

	private function normaliseDumpStatus( array $dumpStatus ) :array {
		$normalised = [];
		foreach ( $dumpStatus as $table => $status ) {
			if ( !\is_string( $table ) || !\is_array( $status ) ) {
				throw new \InvalidArgumentException( 'Worpdrive export map must be keyed by table name with array status values.' );
			}
			$normalised[ $table ] = $this->normaliseTableStatus( $table, $status );
		}
		return $normalised;
	}

	private function normaliseTableStatus( string $table, array $status ) :array {
		$this->assertAllowedTable( $table );

		$normalised = [
			'offset'        => $this->readNonNegativeInt( $status, 'offset' ),
			'page'          => $this->readNonNegativeInt( $status, 'page' ),
			'completed_at'  => $this->readNonNegativeInt( $status, 'completed_at' ),
			'exported_rows' => $this->readNonNegativeInt( $status, 'exported_rows' ),
			'max_page_rows' => $this->readPositiveInt( $status, 'max_page_rows', 1000 ),
			'chunk_size'    => $this->readPositiveInt( $status, 'chunk_size' ),
		];

		$cursor = $this->normaliseCursor( $status[ 'cursor' ] ?? null );
		if ( $cursor !== null ) {
			$normalised[ 'cursor' ] = $cursor;
		}

		return $normalised;
	}

	private function normaliseCursor( $cursor ) :?array {
		return ( new CompositePrimaryKeyCursor() )->normalise( $cursor )
			   ?? ( new UniqueIndexCursor() )->normalise( $cursor );
	}

	private function assertAllowedTable( string $table ) :void {
		if ( $table === '' || \strpos( $table, "\0" ) !== false ) {
			throw new \InvalidArgumentException( 'Worpdrive export map table name is invalid.' );
		}
		if ( $this->allowedTables !== null && !\in_array( $table, $this->allowedTables, true ) ) {
			throw new \InvalidArgumentException( \sprintf( 'Table is not available for Worpdrive export: %s', $table ) );
		}
	}

	private function readNonNegativeInt( array $status, string $key, ?int $default = null ) :int {
		$value = $this->readInt( $status, $key, $default );
		if ( $value < 0 ) {
			throw new \InvalidArgumentException( \sprintf( 'Worpdrive export map value must be zero or greater: %s', $key ) );
		}
		return $value;
	}

	private function readPositiveInt( array $status, string $key, ?int $default = null ) :int {
		$value = $this->readInt( $status, $key, $default );
		if ( $value < 1 ) {
			throw new \InvalidArgumentException( \sprintf( 'Worpdrive export map value must be one or greater: %s', $key ) );
		}
		return $value;
	}

	private function readInt( array $status, string $key, ?int $default = null ) :int {
		if ( !\array_key_exists( $key, $status ) ) {
			if ( $default !== null ) {
				return $default;
			}
			throw new \InvalidArgumentException( \sprintf( 'Worpdrive export map is missing required value: %s', $key ) );
		}

		$value = $status[ $key ];
		if ( \is_bool( $value ) || !( \is_int( $value ) || ( \is_string( $value ) && \ctype_digit( $value ) ) ) ) {
			throw new \InvalidArgumentException( \sprintf( 'Worpdrive export map value must be an integer: %s', $key ) );
		}
		return (int)$value;
	}
}
