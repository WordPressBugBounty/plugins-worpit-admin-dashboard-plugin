<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Theme;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Themes;

class Activate extends Base {

	public function process() :ApiResponse {
		return $this->success( [
			'result'           => $this->loadWpFunctionsThemes()->activate( $this->getFile() ),
			'wordpress-themes' => ( new Themes() )->setRequestParams( $this->getRequestParams() )->collect(),
		] );
	}
}