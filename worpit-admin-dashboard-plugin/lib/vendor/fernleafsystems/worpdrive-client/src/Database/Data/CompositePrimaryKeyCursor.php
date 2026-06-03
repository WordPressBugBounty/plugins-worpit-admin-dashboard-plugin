<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

class CompositePrimaryKeyCursor {

	public const STRATEGY = 'composite_primary_key';

	public function normalise( $cursor ) :?array {
		return ( new TupleCursor() )->normalise( $cursor, self::STRATEGY );
	}

	public function buildPredicate( array $columns, ?array $cursor ) :?string {
		return ( new TupleCursor() )->buildPredicate( $columns, $cursor, self::STRATEGY );
	}

	public function cursorForRow( array $columns, array $row, array $columnMetadata ) :?array {
		return ( new TupleCursor() )->cursorForRow( $columns, $row, $columnMetadata, self::STRATEGY );
	}

	public function columnsMatchCursor( array $columns, array $cursor ) :bool {
		return ( new TupleCursor() )->matchesCursor( $columns, $cursor, self::STRATEGY );
	}
}
