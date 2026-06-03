<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

class UniqueIndexCursor {

	public const STRATEGY = 'unique_index';

	public function normalise( $cursor ) :?array {
		return ( new TupleCursor() )->normalise( $cursor, self::STRATEGY, true );
	}

	public function buildPredicate( array $columns, string $index, ?array $cursor ) :?string {
		return ( new TupleCursor() )->buildPredicate( $columns, $cursor, self::STRATEGY, $index );
	}

	public function cursorForRow( array $columns, string $index, array $row, array $columnMetadata ) :?array {
		return ( new TupleCursor() )->cursorForRow( $columns, $row, $columnMetadata, self::STRATEGY, $index );
	}

	public function columnsMatchCursor( array $columns, string $index, array $cursor ) :bool {
		return ( new TupleCursor() )->matchesCursor( $columns, $cursor, self::STRATEGY, $index );
	}
}
