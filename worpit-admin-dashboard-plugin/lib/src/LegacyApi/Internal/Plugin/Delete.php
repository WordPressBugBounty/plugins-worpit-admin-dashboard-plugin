<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Plugins;

class Delete extends Base {

	public function process() :ApiResponse {

		$result = Handlers\Plugins::Instance()->delete( $this->getFile(), $this->getActionParam( 'site_is_wpms' ) );

		wp_cache_flush(); // since we've deleted a plugin, we need to ensure our collection is up-to-date rebuild.

		return $result ? $this->success( [
			'result'            => true,
			'wordpress-plugins' => ( new Plugins() )->setRequestParams( $this->getRequestParams() )->collect(),
		] ) : $this->fail();
	}
}