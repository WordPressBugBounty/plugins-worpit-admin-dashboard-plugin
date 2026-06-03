<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		if ( $this->getRequestParams()->a == 'check' ) {
			$r = $this->getStandardResponse();
			$r->success = true;
			return $r;
		}
		return $this->processAction();
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function processAction() {
		/** @var \ICWP_APP_FeatureHandler_Plugin $mod */
		$mod = $this->mod;
		$params = $this->getRequestParams();
		$response = $this->getStandardResponse();

		try {
			if ( $mod->getIsSiteLinked() ) {
				throw new \Exception( 'AlreadyAssigned', 1 );
			}
			if ( empty( $params->key ) ) {
				throw new \Exception( $mod::API_MESSAGE_INVALID_BOOTSTRAP_CREDENTIALS, 2 );
			}
			if ( !$mod->isValidBootstrapRequestKey( (string)$params->key ) ) {
				throw new \Exception( $mod::API_MESSAGE_INVALID_BOOTSTRAP_CREDENTIALS, 3 );
			}
			if ( empty( $params->pin ) ) {
				throw new \Exception( 'PinEmpty', 4 );
			}
			if ( empty( $params->accname ) ) {
				throw new \Exception( 'AccountEmpty', 5 );
			}
			if ( !is_email( $params->accname ) ) {
				throw new \Exception( 'AccountNotValid', 6 );
			}

			$enc = $this->loadEncryptProcessor();
			if ( $enc->getSupportsOpenSslSign() ) {
				$publicKey = $mod->getIcwpPublicKey();
				if ( !empty( $params->opensig ) && !empty( $publicKey ) ) {
					$sslSuccess = $enc->verifySslSignature( $params->verification_code, $params->opensig, $publicKey );
					$response->openssl_verify = $sslSuccess;
					if ( $sslSuccess !== 1 ) {
						throw new \Exception( 'Failed to Verify SSL Signature.', 7 );
					}
				}
			}

			$mod->setPluginPin( $params->pin );
			$mod->setAssignedAccount( $params->accname );
			$mod->closeBootstrapWindow();
			$response->success = true;
		}
		catch (\Exception $e) {
			$response->success = false;
			$response->message = $e->getMessage();
			$response->status = $e->getMessage();
			$response->code = $e->getCode();
		}

		return $response;
	}
}
