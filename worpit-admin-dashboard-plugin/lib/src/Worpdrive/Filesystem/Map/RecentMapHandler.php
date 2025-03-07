<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class RecentMapHandler extends MapHandler {

	protected function dbFile() :string {
		return 'recent_map.sqlite';
	}
}