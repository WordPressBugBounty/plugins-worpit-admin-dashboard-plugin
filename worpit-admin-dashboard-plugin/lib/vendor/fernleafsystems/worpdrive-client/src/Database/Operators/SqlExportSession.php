<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Operators;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class SqlExportSession {

	private array $originalValues = [];

	private bool $restoreNeeded = false;

	/**
	 * @throws \Exception
	 */
	public function prepareForSchemaExport() :void {
		$this->captureOriginalValues( [ 'sql_mode', 'sql_quote_show_create' ] );
		$this->restoreNeeded = !empty( $this->originalValues );
		$this->runRequired( "SET SESSION sql_mode = ''" );
		$this->runRequired( 'SET SESSION sql_quote_show_create = 1' );
	}

	/**
	 * @throws \Exception
	 */
	public function prepareForDataExport() :void {
		$this->captureOriginalValues( [ 'sql_mode' ] );
		$this->restoreNeeded = !empty( $this->originalValues );
		$this->runRequired( "SET SESSION sql_mode = ''" );
	}

	public function restore() :void {
		if ( !$this->restoreNeeded ) {
			return;
		}

		$db = WorpdriveRuntime::host()->database();
		try {
			if ( \array_key_exists( 'sql_mode', $this->originalValues ) ) {
				$db->doSql( \sprintf(
					'SET SESSION sql_mode = %s',
					( new SqlDumpValueEscaper() )->escape( $this->originalValues[ 'sql_mode' ] )
				) );
			}
			if ( \array_key_exists( 'sql_quote_show_create', $this->originalValues ) ) {
				$db->doSql( \sprintf(
					'SET SESSION sql_quote_show_create = %d',
					(int)$this->originalValues[ 'sql_quote_show_create' ]
				) );
			}
		}
		catch ( \Throwable $e ) {
			// Source-session restore is best-effort; do not hide or rewrite a completed dump during cleanup.
		}
	}

	private function captureOriginalValues( array $names ) :void {
		$db = WorpdriveRuntime::host()->database();
		foreach ( $names as $name ) {
			if ( \array_key_exists( $name, $this->originalValues ) ) {
				continue;
			}
			try {
				$value = $db->getVar( \sprintf( 'SELECT @@SESSION.%s', $name ) );
			}
			catch ( \Throwable $e ) {
				continue;
			}
			if ( \is_scalar( $value ) && $value !== false ) {
				$this->originalValues[ $name ] = $value;
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	private function runRequired( string $sql ) :void {
		if ( WorpdriveRuntime::host()->database()->doSql( $sql ) === false ) {
			throw new \Exception( 'Failed to configure database export session.' );
		}
	}
}
