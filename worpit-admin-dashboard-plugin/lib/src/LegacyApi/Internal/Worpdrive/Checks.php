<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

class Checks extends BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\CompatibilityChecks(
			\is_array( $this->getActionParam( 'check_params', [] ) ) ? $this->getActionParam( 'check_params', [] ) : [],
			$this->getActionParam( 'uuid' ),
			0
		) )->run();
	}
}