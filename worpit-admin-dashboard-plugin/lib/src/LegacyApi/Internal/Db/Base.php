<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Db;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	/**
	 * @throws \Exception
	 */
	public function getDatabaseTableStatus( bool $includeViews = false ) :array {
		$DB = $this->loadDbProcessor();

		$tablesStatus = $DB->showTableStatus();
		if ( empty( $tablesStatus ) ) {
			throw new \Exception( 'Empty results from TABLE STATUS query is not as expected.' );
		}

		$dbTotal = 0;
		$gainTotal = 0;
		$tables = [];
		foreach ( $tablesStatus as $table ) {
			/** @var \stdClass $table */
			if ( !empty( $table->Name ) && \str_starts_with( $table->Name, $this->loadDbProcessor()->getPrefix() ) ) {
				if ( !$DB->isTableView( $table ) || $includeViews ) {

					$tableTotal = $table->Data_length + $table->Index_length;
					$dbTotal += $tableTotal;
					$gainTotal += $table->Data_free;

					$tbl = [
						'name'    => $table->Name,
						'records' => $table->Rows,
						'size'    => $tableTotal,
						'gain'    => $table->Data_free,
						'comment' => empty( $table->Comment ) ? '' : $table->Comment,
						'crashed' => 0
					];

					if ( $DB->isTableCrashed( $table ) ) {
						$tbl[ 'comment' ] = sprintf( 'Table "%s" appears to be crashed', $table->Name );
						$tbl[ 'crashed' ] = 1;
					}

					$tables[] = $tbl;
				}
			}
		}

		return [
			'tables'         => $tables,
			'database_total' => $dbTotal,
			'database_gain'  => $gainTotal
		];
	}
}