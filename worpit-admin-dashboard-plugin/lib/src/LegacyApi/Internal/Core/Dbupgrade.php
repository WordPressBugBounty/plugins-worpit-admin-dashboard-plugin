<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Core;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Dbupgrade extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public function process() :ApiResponse {
		$this->loadWP()->doWpUpgrade();
		return $this->success();
	}
}