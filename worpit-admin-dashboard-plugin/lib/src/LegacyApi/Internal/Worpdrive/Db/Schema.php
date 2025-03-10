<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Db;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Schema\SchemaHandler;

class Schema extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new SchemaHandler(
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit(),
		) )->run();
	}
}