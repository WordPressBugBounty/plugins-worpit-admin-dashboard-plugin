<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

class Checks extends BaseWorpdrive {

	protected function execHandler() :?array {
		$checkParams = $this->getActionParam( 'check_params', [] );
		return ( new IControlWorpdriveCompatibilityChecks(
			\is_array( $checkParams ) ? $checkParams : [],
			$this->getActionParam( 'uuid' ),
			0
		) )->run();
	}
}
