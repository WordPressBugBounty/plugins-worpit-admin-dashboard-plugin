<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\WorpdriveClient\Clean as WorpdriveClean;

class Clean extends BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new WorpdriveClean(
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit()
		) )->run();
	}
}
