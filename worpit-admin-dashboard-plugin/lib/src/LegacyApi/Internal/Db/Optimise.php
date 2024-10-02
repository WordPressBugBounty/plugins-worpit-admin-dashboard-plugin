<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Db;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Optimise extends Base {

	public function process() :ApiResponse {
		try {
			return $this->success( $this->optimiseDatabase() );
		}
		catch ( \Exception $e ) {
			return $this->fail( $e->getMessage() );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function optimiseDatabase() :array {
		$status = $this->getDatabaseTableStatus();
		if ( empty( $status[ 'tables' ] ) ) {
			throw new \Exception( 'Empty results from TABLE STATUS query is not expected.' );
		}
		foreach ( $status[ 'tables' ] as $aTable ) {
			if ( $aTable[ 'gain' ] > 0 ) {
				$this->loadDbProcessor()->optimizeTable( $aTable[ 'name' ] );
			}
		}
		return $this->getDatabaseTableStatus();
	}
}