<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\User;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Logout extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public function process() :ApiResponse {
		if ( $this->loadWpUsers()->isUserLoggedIn() ) {
			$this->loadWpUsers()->logoutUser();
		}
		return $this->success();
	}
}