<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Info extends Base {

	public function collect() :array {
		return [
			'capabilities'       => ( new Capabilities() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-info'     => ( new WordPress() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-paths'    => ( new Paths() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-plugins'  => ( new Plugins() )->setRequestParams( $this->getRequestParams() )->collect(),
			'wordpress-themes'   => ( new Themes() )->setRequestParams( $this->getRequestParams() )->collect(),
			'force_update_check' => $this->isForceUpdateCheck() ? 1 : 0,
		];
	}

	public function process() :ApiResponse {
		( new \FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload\Clean() )
			->setCon( $this->getCon() )
			->run();
		return $this->success( $this->collect() );
	}
}