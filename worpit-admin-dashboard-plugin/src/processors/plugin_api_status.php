<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_Processor_Plugin_Api_Status extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function processAction() {
		return $this->setSuccessResponse( 'Status', 0, $this->getStatusData() );
	}

	protected function getStatusData() :array {
		$con = self::con();
		return [
			'plugin_status'      => 1,
			'plugin_version'     => $con->getVersion(),
			'plugin_url'         => $con->getPluginUrl(),
			'supported_internal' => $this->mod->getSupportedInternalApiAction(),
			'supported_modules'  => $this->mod->getSupportedModules(),
			'supported_channels' => $this->mod->getPermittedApiChannels(),
			'supported_openssl'  => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			'wpe_api'            => defined( 'WPE_API' ),
		];
	}
}
