<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Db;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	/**
	 * @throws \Exception
	 */
	public function getDatabaseTableStatus( bool $includeViews = false ) :array {
		$DB = $this->loadDbProcessor();

		$aTableStatusResults = $DB->showTableStatus();
		if ( empty( $aTableStatusResults ) ) {
			throw new \Exception( 'Empty results from TABLE STATUS query is not as expected.' );
		}

		$nDatabaseTotal = 0;
		$nGainTotal = 0;
		$tables = [];
		/** @var \stdClass $oTable */
		foreach ( $aTableStatusResults as $oTable ) {
			$nDataLength = $oTable->Data_length;
			$nIndexLength = $oTable->Index_length;
			$nDataFree = $oTable->Data_free;

			$nTableTotal = $nDataLength + $nIndexLength;
			$nDatabaseTotal += $nTableTotal;
			$nGainTotal += $nDataFree;

			$sComment = empty( $oTable->Comment ) ? '' : $oTable->Comment;

			if ( !$DB->isTableView( $oTable ) || $includeViews ) {

				$table = [
					'name'    => $oTable->Name,
					'records' => $oTable->Rows,
					'size'    => $nTableTotal,
					'gain'    => $nDataFree,
					'comment' => $sComment,
					'crashed' => 0
				];

				if ( $DB->isTableCrashed( $oTable ) ) {
					$table[ 'comment' ] = sprintf( 'Table "%s" appears to be crashed', $oTable->Name );
					$table[ 'crashed' ] = 1;
				}

				$tables[] = $table;
			}
		}

		return [
			'tables'         => $tables,
			'database_total' => $nDatabaseTotal,
			'database_gain'  => $nGainTotal
		];
	}
}