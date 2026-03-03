<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

class Map extends BaseMap {

	protected function getMapType() :string {
		return 'full';
	}
}