<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Plugins;

class Deactivate extends Base {

	public function process() :ApiResponse {
		$file = $this->getFile();
		$this->loadWpPlugins()->deactivate( $file, $this->getActionParam( 'site_is_wpms' ) );
		return $this->success( [
			'result'        => !$this->loadWpPlugins()->getIsActive( $file ),
			'single-plugin' => ( new Plugins() )
								   ->setRequestParams( $this->getRequestParams() )
								   ->collect()[ $file ] ?? false,
		] );
	}
}