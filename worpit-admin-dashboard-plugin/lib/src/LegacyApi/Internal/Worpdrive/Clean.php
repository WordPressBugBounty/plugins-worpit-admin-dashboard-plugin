<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

class Clean extends BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Clean(
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit()
		) )->run();
	}
}