<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\User;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Delete extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public function process() :ApiResponse {
		try {
			return $this->success( [
				'result' => $this->loadWpUsers()->deleteUser(
					(int)$this->getActionParam( 'user_id' ),
					false,
					$this->getActionParam( 'reassign_id' )
				)
			] );
		}
		catch ( \Exception $e ) {
			return $this->fail( $e->getMessage() );
		}
	}
}