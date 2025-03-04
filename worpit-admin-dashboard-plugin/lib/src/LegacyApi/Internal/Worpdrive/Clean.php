<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Clean extends BaseWorpdrive {

	/**
	 * @throws \Exception
	 */
	public function process() :ApiResponse {
		if ( empty( $this->getActionParam( 'uuid' ) ) ) {
			throw new \Exception( 'uuid param is empty' );
		}
		( new \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Clean(
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit()
		) )->all();
		return $this->success();
	}
}