<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

use FernleafSystems\WorpdriveClient\Database\Operators\{
	SqlDumpIdentifierEscaper,
	SqlDumpValueEscaper
};

class TupleCursor {

	private const ENCODING_BASE64 = 'base64';

	public function normalise( $cursor, string $strategy, bool $requiresIndex = false ) :?array {
		if ( $cursor === null || !\is_array( $cursor ) ) {
			return null;
		}

		try {
			if ( ( $cursor[ 'strategy' ] ?? null ) !== $strategy ) {
				throw new \InvalidArgumentException();
			}
			$columns = $this->normaliseColumns( $cursor[ 'columns' ] ?? null );
			$values = $this->normaliseValues( $cursor[ 'values' ] ?? null );
			if ( \count( $columns ) !== \count( $values ) ) {
				throw new \InvalidArgumentException();
			}

			if ( $requiresIndex ) {
				return [
					'strategy' => $strategy,
					'index'    => $this->normaliseIndex( $cursor[ 'index' ] ?? null ),
					'columns'  => $columns,
					'values'   => $values,
				];
			}
			return [
				'strategy' => $strategy,
				'columns'  => $columns,
				'values'   => $values,
			];
		}
		catch ( \InvalidArgumentException $e ) {
			return null;
		}
	}

	public function buildPredicate( array $columns, ?array $cursor, string $strategy, ?string $index = null ) :?string {
		if ( $cursor === null || !$this->matchesCursor( $columns, $cursor, $strategy, $index ) ) {
			return null;
		}

		$values = $cursor[ 'values' ];
		$idEscaper = new SqlDumpIdentifierEscaper();
		$clauses = [];
		foreach ( $columns as $i => $column ) {
			$parts = [];
			for ( $j = 0; $j < $i; $j++ ) {
				$value = $this->sqlLiteral( $values[ $j ] );
				if ( $value === null ) {
					return null;
				}
				$parts[] = \sprintf( '%s = %s', $idEscaper->escape( $columns[ $j ] ), $value );
			}

			$value = $this->sqlLiteral( $values[ $i ] );
			if ( $value === null ) {
				return null;
			}
			$parts[] = \sprintf( '%s > %s', $idEscaper->escape( $column ), $value );
			$clauses[] = '('.\implode( ' AND ', $parts ).')';
		}

		return empty( $clauses ) ? null : '('.\implode( ' OR ', $clauses ).')';
	}

	public function cursorForRow( array $columns, array $row, array $columnMetadata, string $strategy, ?string $index = null ) :?array {
		$values = [];
		foreach ( $columns as $column ) {
			if ( !\array_key_exists( $column, $row ) || $row[ $column ] === null ) {
				return null;
			}
			$value = $this->valueForRow( $row[ $column ], $columnMetadata[ $column ] ?? [] );
			if ( $value === null ) {
				return null;
			}
			$values[] = $value;
		}

		if ( $index !== null ) {
			return [
				'strategy' => $strategy,
				'index'    => $index,
				'columns'  => \array_values( $columns ),
				'values'   => $values,
			];
		}
		return [
			'strategy' => $strategy,
			'columns'  => \array_values( $columns ),
			'values'   => $values,
		];
	}

	public function matchesCursor( array $columns, array $cursor, string $strategy, ?string $index = null ) :bool {
		if ( ( $cursor[ 'strategy' ] ?? null ) !== $strategy
			 || ( $cursor[ 'columns' ] ?? null ) !== \array_values( $columns )
			 || !\is_array( $cursor[ 'values' ] ?? null )
			 || \count( $cursor[ 'values' ] ) !== \count( $columns ) ) {
			return false;
		}
		return $index === null || ( $cursor[ 'index' ] ?? null ) === $index;
	}

	private function normaliseIndex( $index ) :string {
		if ( !\is_string( $index ) || $index === '' || \strpos( $index, "\0" ) !== false ) {
			throw new \InvalidArgumentException();
		}
		return $index;
	}

	private function normaliseColumns( $columns ) :array {
		if ( !\is_array( $columns ) || !$this->isList( $columns ) || empty( $columns ) ) {
			throw new \InvalidArgumentException();
		}

		$normalised = [];
		foreach ( $columns as $column ) {
			if ( !\is_string( $column ) || $column === '' || \strpos( $column, "\0" ) !== false ) {
				throw new \InvalidArgumentException();
			}
			$normalised[] = $column;
		}
		return $normalised;
	}

	private function normaliseValues( $values ) :array {
		if ( !\is_array( $values ) || !$this->isList( $values ) ) {
			throw new \InvalidArgumentException();
		}

		return \array_map(
			function ( $value ) {
				return $this->normaliseValue( $value );
			},
			$values
		);
	}

	private function normaliseValue( $value ) {
		if ( \is_int( $value ) ) {
			return (string)$value;
		}
		if ( \is_string( $value ) ) {
			if ( !$this->isJsonSafeString( $value ) ) {
				throw new \InvalidArgumentException();
			}
			return $value;
		}
		if ( \is_array( $value ) ) {
			return $this->normaliseBase64Marker( $value );
		}

		throw new \InvalidArgumentException();
	}

	private function normaliseBase64Marker( array $value ) :array {
		if ( \count( $value ) !== 2
			 || ( $value[ 'encoding' ] ?? null ) !== self::ENCODING_BASE64
			 || !\array_key_exists( 'value', $value )
			 || !\is_string( $value[ 'value' ] ) ) {
			throw new \InvalidArgumentException();
		}

		$decoded = \base64_decode( $value[ 'value' ], true );
		if ( $decoded === false ) {
			throw new \InvalidArgumentException();
		}

		return [
			'encoding' => self::ENCODING_BASE64,
			'value'    => \base64_encode( $decoded ),
		];
	}

	private function valueForRow( $value, array $columnMetadata ) {
		if ( \is_int( $value ) ) {
			return (string)$value;
		}
		if ( \is_bool( $value ) ) {
			return $value ? '1' : '0';
		}
		if ( \is_float( $value ) ) {
			return (string)$value;
		}
		if ( \is_string( $value ) ) {
			return $this->isBinaryColumn( $columnMetadata ) || !$this->isJsonSafeString( $value )
				? [
					'encoding' => self::ENCODING_BASE64,
					'value'    => \base64_encode( $value ),
				]
				: $value;
		}
		return null;
	}

	private function sqlLiteral( $value ) :?string {
		if ( \is_string( $value ) ) {
			return ( new SqlDumpValueEscaper() )->escape( $value );
		}
		if ( \is_array( $value ) && ( $value[ 'encoding' ] ?? null ) === self::ENCODING_BASE64
			 && \is_string( $value[ 'value' ] ?? null ) ) {
			$decoded = \base64_decode( $value[ 'value' ], true );
			return $decoded === false ? null : "X'".\bin2hex( $decoded )."'";
		}
		return null;
	}

	private function isBinaryColumn( array $columnMetadata ) :bool {
		return \preg_match(
			'#^(?:blob|longblob|mediumblob|tinyblob|binary|varbinary|bit)\b#i',
			(string)( $columnMetadata[ 'Type' ] ?? '' )
		) === 1;
	}

	private function isJsonSafeString( string $value ) :bool {
		return \json_encode( $value ) !== false;
	}

	private function isList( array $values ) :bool {
		if ( empty( $values ) ) {
			return true;
		}
		return \array_keys( $values ) === \range( 0, \count( $values ) - 1 );
	}
}
