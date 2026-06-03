<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

class DatabaseExportErrorPayload {

	private const CODE_FAILED = 'db_export_failed';

	/**
	 * @return array{error:array{code:string,message:string,stage:string,retryable:bool},error_context:array{table_export_map:array}}
	 */
	public function build( \Throwable $error, array $tableExportMap = [], ?string $codeHint = null ) :array {
		try {
			$classification = $this->classify( $error, $codeHint );
			return $this->payload(
				$classification[ 'code' ],
				$classification[ 'message' ],
				$this->safeTableExportMap( $tableExportMap )
			);
		}
		catch ( \Throwable $e ) {
			return $this->payload( self::CODE_FAILED, $this->messageForCode( self::CODE_FAILED ), [] );
		}
	}

	private function payload( string $code, string $message, array $tableExportMap ) :array {
		return [
			'error'         => [
				'code'      => $code,
				'message'   => $message,
				'stage'     => 'database_data',
				'retryable' => true,
			],
			'error_context' => [
				'table_export_map' => $tableExportMap,
			],
		];
	}

	/**
	 * @return array{code:string,message:string}
	 */
	private function classify( \Throwable $error, ?string $codeHint ) :array {
		if ( $codeHint === 'db_export_invalid_map' || $codeHint === 'db_export_zip_failed' ) {
			return [
				'code'    => $codeHint,
				'message' => $this->messageForCode( $codeHint ),
			];
		}

		$message = $error->getMessage();
		$queryPrefix = 'Database query failed for table: ';
		if ( \strpos( $message, $queryPrefix ) === 0 ) {
			return $this->forTableFailure(
				'db_export_query_failed',
				'Database query failed during table export.',
				'Database query failed for table: %s',
				\substr( $message, \strlen( $queryPrefix ) )
			);
		}

		$openPrefix = 'Failed to open dump file for table: ';
		if ( \strpos( $message, $openPrefix ) === 0 ) {
			return $this->forTableFailure(
				'db_export_open_failed',
				'Failed to open database dump file.',
				'Failed to open database dump file for table: %s',
				\substr( $message, \strlen( $openPrefix ) )
			);
		}

		if ( \strpos( $message, 'Dump file directory is not writable.' ) === 0 ) {
			return [
				'code'    => 'db_export_open_failed',
				'message' => 'Failed to open database dump file.',
			];
		}

		if ( \strpos( $message, 'Failed to write Worpdrive database dump content' ) === 0 ) {
			return [
				'code'    => 'db_export_write_failed',
				'message' => 'Failed to write database dump content.',
			];
		}

		if ( \strpos( $message, 'Export failed to make progress for table ' ) === 0 ) {
			$table = \preg_match( '#^Export failed to make progress for table (.+?)(?: -|$)#', $message, $matches )
				? $matches[ 1 ]
				: '';
			return $this->forTableFailure(
				'db_export_no_progress',
				'Database export failed to make progress.',
				'Database export failed to make progress for table: %s',
				$table
			);
		}

		if ( \strpos( $message, 'Export exceeded maximum iterations' ) === 0 ) {
			return [
				'code'    => 'db_export_no_progress',
				'message' => 'Database export failed to make progress.',
			];
		}

		if ( $this->isZipFailure( $message ) ) {
			return [
				'code'    => 'db_export_zip_failed',
				'message' => $this->messageForCode( 'db_export_zip_failed' ),
			];
		}

		return [
			'code'    => self::CODE_FAILED,
			'message' => $this->messageForCode( self::CODE_FAILED ),
		];
	}

	/**
	 * @return array{code:string,message:string}
	 */
	private function forTableFailure( string $code, string $fallbackMessage, string $tableMessage, string $table ) :array {
		$safeTable = $this->safeName( $table );
		return [
			'code'    => $code,
			'message' => $safeTable === null ? $fallbackMessage : \sprintf( $tableMessage, $safeTable ),
		];
	}

	private function isZipFailure( string $message ) :bool {
		foreach ( [
			'Failed to create new Zip file',
			'Failed to write the new ZIP file',
			'Failed to create new Zip file with PclZip',
			'"\\ZipArchive" is not available',
			'Invalid WorpDrive zip path.',
		] as $prefix ) {
			if ( \strpos( $message, $prefix ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	private function messageForCode( string $code ) :string {
		switch ( $code ) {
			case 'db_export_invalid_map':
				return 'Database export map is invalid.';
			case 'db_export_zip_failed':
				return 'Failed to create database export archive.';
			default:
				return 'Database data export failed.';
		}
	}

	private function safeName( string $value ) :?string {
		$value = (string)\preg_replace( '~[\x00-\x1F\x7F]+~', '', $value );
		$value = \trim( $value );
		if ( $value === '' || \preg_match( '#(?:^[A-Za-z]:[\\\\/]|[\\\\/])#', $value ) === 1 ) {
			return null;
		}
		return \strlen( $value ) > 128 ? \substr( $value, 0, 128 ) : $value;
	}

	private function safeTableExportMap( array $tableExportMap ) :array {
		$safe = [];
		foreach ( $tableExportMap as $table => $status ) {
			if ( !\is_string( $table ) || !\is_array( $status ) ) {
				continue;
			}
			$safeTable = $this->safeContextString( $table );
			if ( $safeTable !== '' ) {
				$safe[ $safeTable ] = $this->safeContextArray( $status, 0 );
			}
		}
		return $safe;
	}

	private function safeContextArray( array $data, int $depth ) :array {
		if ( $depth > 4 ) {
			return [];
		}

		$safe = [];
		foreach ( $data as $key => $value ) {
			if ( !\is_int( $key ) && !\is_string( $key ) ) {
				continue;
			}
			$safeKey = \is_int( $key ) ? $key : $this->safeContextString( $key );
			if ( $safeKey === '' ) {
				continue;
			}
			$safe[ $safeKey ] = \is_array( $value )
				? $this->safeContextArray( $value, $depth + 1 )
				: $this->safeContextValue( $value );
		}
		return $safe;
	}

	private function safeContextValue( $value ) {
		if ( $value === null || \is_bool( $value ) || \is_int( $value ) || \is_float( $value ) ) {
			return $value;
		}
		if ( \is_string( $value ) ) {
			return $this->safeContextString( $value );
		}
		return null;
	}

	private function safeContextString( string $value ) :string {
		$value = (string)\preg_replace( '~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+~', '', $value );
		return \strlen( $value ) > 256 ? \substr( $value, 0, 256 ) : $value;
	}
}
