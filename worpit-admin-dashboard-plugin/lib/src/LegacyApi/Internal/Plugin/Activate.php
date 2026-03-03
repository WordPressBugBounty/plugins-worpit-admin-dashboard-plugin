<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\Plugins;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

class Activate extends Base {

	public function process() :ApiResponse {
		$file = $this->getFile();
		return $this->success( [
			'result'        => Plugins::Instance()->activate( $file, $this->getActionParam( 'site_is_wpms' ) ),
			'single-plugin' => ( new Collect\Plugins() )
								   ->setRequestParams( $this->getRequestParams() )
												->collect()[ $file ] ?? false,
		] );
	}
}