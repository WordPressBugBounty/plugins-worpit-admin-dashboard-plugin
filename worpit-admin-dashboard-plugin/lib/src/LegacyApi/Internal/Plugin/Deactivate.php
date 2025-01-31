<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\Plugins;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

class Deactivate extends Base {

	public function process() :ApiResponse {
		$file = $this->getFile();
		Plugins::Instance()->deactivate( $file, $this->getActionParam( 'site_is_wpms' ) );
		return $this->success( [
			'result'        => !Plugins::Instance()->isActive( $file ),
			'single-plugin' => ( new Collect\Plugins() )
								   ->setRequestParams( $this->getRequestParams() )
								   ->collect()[ $file ] ?? false,
		] );
	}
}