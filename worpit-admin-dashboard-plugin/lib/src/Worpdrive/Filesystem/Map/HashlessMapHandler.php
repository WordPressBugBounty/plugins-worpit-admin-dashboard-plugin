<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class HashlessMapHandler extends MapHandler {

	protected function dbFile() :string {
		return 'hashless_map.sqlite';
	}
}