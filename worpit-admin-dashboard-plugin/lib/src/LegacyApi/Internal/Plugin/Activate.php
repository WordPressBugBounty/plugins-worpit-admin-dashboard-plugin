<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Plugins;

class Activate extends Base {

	public function process() :ApiResponse {
		$file = $this->getFile();
		return $this->success( [
			'result'        => $this->loadWpPlugins()->activate( $file, $this->getActionParam( 'site_is_wpms' ) ),
			'single-plugin' => ( new Plugins() )->setRequestParams( $this->getRequestParams() )
												->collect()[ $file ] ?? false,
		] );
	}
}