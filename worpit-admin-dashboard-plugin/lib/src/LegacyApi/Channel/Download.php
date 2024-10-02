<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Channel;

use FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

class Download extends \ICWP_APP_Processor_Plugin_Api {

	/**
	 * Override so that we don't run the handshaking etc.
	 * @return \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		try {
			$this->processAction();
		}
		catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
		return $this->setSuccessResponse();
	}

	/**
	 * @throws \Exception
	 */
	protected function processAction() {
		$this->getStandardResponse()->die = true;

		( new ZipDownload\Download() )
			->setCon( $this->getController() )
			->byID( $this->getRequestParams()->zip_id );
		die();
	}
}