<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		if ( $this->getRequestParams()->a == 'check' ) {
			return $this->getStandardResponse()->setSuccess( true );
		}
		return $this->processAction();
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function processAction() {
		$params = $this->getRequestParams();
		$response = $this->getStandardResponse();

		if ( $this->mod->getIsSiteLinked() ) {
			return $response->setMessage( 'Assigned To:'.$this->mod->getOpt( 'assigned_to' ) )
							->setStatus( 'AlreadyAssigned' )
							->setCode( 1 )
							->setSuccess( false );
		}

		if ( empty( $params->key ) ) {
			return $response->setMessage( 'KeyEmpty:'.'.' )
							->setCode( 2 );
		}
		if ( $params->key != $this->mod->getPluginAuthKey() ) {
			return $response->setMessage( 'KeyMismatch:'.$params->key.'.' )
							->setCode( 3 );
		}

		if ( empty( $params->pin ) ) {
			return $response->setMessage( 'PinEmpty:.' )
							->setCode( 4 );
		}

		if ( empty( $params->accname ) ) {
			return $response->setMessage( 'AccountEmpty:.' )
							->setCode( 5 );
		}
		if ( !is_email( $params->accname ) ) {
			return $response->setMessage( 'AccountNotValid:'.$params->accname )
							->setCode( 6 );
		}

		$oEncryptProcessor = $this->loadEncryptProcessor();
		if ( $oEncryptProcessor->getSupportsOpenSslSign() ) {

			$sPublicKey = $this->mod->getIcwpPublicKey();
			if ( !empty( $params->opensig ) && !empty( $sPublicKey ) ) {
				$nSslSuccess = $oEncryptProcessor->verifySslSignature(
					$params->verification_code, $params->opensig, $sPublicKey
				);
				$response->openssl_verify = $nSslSuccess;
				if ( $nSslSuccess !== 1 ) {
					$response->message = 'Failed to Verify SSL Signature.';
					$response->code = 7;
					return $response;
				}
			}
		}

		$this->mod->setPluginPin( $params->pin );
		$this->mod->setAssignedAccount( $params->accname );
		return $response->setSuccess( true );
	}
}