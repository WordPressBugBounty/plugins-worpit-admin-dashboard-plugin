<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Db;

use FernleafSystems\WorpdriveClient\Database\Schema\SchemaHandler;

class Schema extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new SchemaHandler(
			$this->dumpMethod(),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit()
		) )->run();
	}

	private function dumpMethod() :string {
		$m = $this->getActionParam( 'dump_method' );
		return \in_array( $m, [ 'zip', 'direct' ] ) ? $m : 'direct';
	}
}
