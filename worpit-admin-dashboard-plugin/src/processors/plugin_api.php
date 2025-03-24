<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Time\WorldTimeApi;

abstract class ICWP_APP_Processor_Plugin_Api extends \ICWP_APP_Processor_BaseApp {

	protected static LegacyApi\ApiResponse $actionResponse;

	/**
	 * @var string
	 */
	protected $sLoggedInUser;

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function run() {
		$actionResponse = self::getStandardResponse();
		$this->preActionVerify();
		if ( $actionResponse->success ) {
			$this->preActionEnvironmentSetup();
			$this->processAction();
		}
		$this->postProcessAction();
		return $actionResponse;
	}

	protected function preActionVerify() {
		$r = $this->getStandardResponse();
		$r->channel = $this->getApiChannel();

		$this->preApiCheck();

		if ( !$r->success ) {
			if ( !$this->attemptSiteReassign()->success ) {
				return;
			}
		}

		$this->handshake();

		if ( !$r->success ) {
			if ( $r->code == 9991 ) {
				$this->mod->setCanHandshake(); //recheck ability to handshake
			}
		}
	}

	protected function postProcessAction() {
		$oR = $this->getStandardResponse();
		$aData = $oR->data;
		$aData[ 'verification_code' ] = $this->getRequestParams()->verification_code;
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	abstract protected function processAction();

	/**
	 * @return string
	 */
	protected function getApiChannel() {
		$oParams = $this->getRequestParams();
		return \in_array( $oParams->m, $this->mod->getPermittedApiChannels() ) ? $oParams->m : 'index';
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function preApiCheck() {
		$oReqParams = $this->getRequestParams();
		$oResponse = $this->getStandardResponse();

		if ( !$this->mod->getIsSiteLinked() ) {
			$sErrorMessage = 'NotAssigned';
			return $this->setErrorResponse(
				$sErrorMessage,
				9999
			);
		}

		if ( empty( $oReqParams->key ) ) {
			$sErrorMessage = 'EmptyRequestKey';
			return $this->setErrorResponse(
				$sErrorMessage,
				9995
			);
		}

		if ( $oReqParams->key != $this->mod->getPluginAuthKey() ) {
			$sErrorMessage = 'InvalidKey';
			return $this->setErrorResponse(
				$sErrorMessage,
				9998
			);
		}

		if ( empty( $oReqParams->pin ) ) {
			$sErrorMessage = 'EmptyRequestPin';
			return $this->setErrorResponse(
				$sErrorMessage,
				9994
			);
		}
		$sPin = $this->mod->getPluginPin();
		if ( md5( $oReqParams->pin ) != $sPin ) {
			$sErrorMessage = 'InvalidPin';
			return $this->setErrorResponse(
				$sErrorMessage,
				9997
			);
		}

		return $oResponse;
	}

	/**
	 * Attempts to relink/reassign a site upon API failure, with certain pre-conditions
	 *
	 * 1) The channel is "retrieve"
	 * 2) The site CAN Handshake (it will check this)
	 * 3) The handshake is verified for this package
	 *
	 * @return LegacyApi\ApiResponse
	 */
	protected function attemptSiteReassign() {
		$req = $this->getRequestParams();

		if ( empty( $req->m ) || !in_array( $req->m, [ 'auth', 'internal', 'retrieve' ] ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Site action method is neither "retrieve" nor "internal".' ),
				9806
			);
		}

		// We first verify fully if we CAN handshake
		if ( !$this->mod->getCanHandshake( true ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Site cannot handshake' ),
				9801
			);
		}

		$this->handshake();
		$r = $this->getStandardResponse();

		if ( !$r->success ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Handshake verify failed' ),
				9802
			);
		}

		if ( empty( $req->accname ) || !is_email( $req->accname ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Request account empty or invalid' ),
				9803
			);
		}

		if ( empty( $req->key ) || strlen( $req->key ) != 24 ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Auth Key not of the correct format' ),
				9804
			);
		}

		if ( empty( $req->pin ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'PIN empty' ),
				9805
			);
		}

		$this->mod->setOpt( 'key', $req->key );
		$this->mod->setAssignedAccount( $req->accname );
		$this->mod->setPluginPin( $req->pin );
		$this->mod->savePluginOptions();

		return $this->setSuccessResponse(
			'Attempting Site Reassign Succeeded.',
			9800
		);
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function handshake() {
		$req = $this->getRequestParams();
		$response = $this->getStandardResponse();

		if ( !$this->mod->getCanHandshake() ) {
			$response->handshake = 'unsupported';
			return $response;
		}

		$response->handshake = 'failed';
		try {
			$this->publicKeyVerify();
			return $this->setSuccessResponse();
		}
		catch ( \Exception $e ) {
		}

		try {
			if ( $this->verifyHmac() ) {
				return $this->setSuccessResponse();
			}
		}
		catch ( \Exception $e ) {
		}

		if ( empty( $req->package_name ) || empty( $req->pin ) ) {
			return $this->setErrorResponse( 'Package Name or PIN were empty. Could not Handshake.', 9990 );
		}

		if ( \str_starts_with( $req->package_name, 'worpdrive_' ) ) {
			return $this->setErrorResponse( "Package verification isn't supported for worpdrive actions.", 9993 );
		}

		// We can do this because we've assumed at this point we've validated the communication with iControlWP
		$verifyURL = sprintf(
			'%s/%s/%s/%s',
			\rtrim( $this->mod->getAppUrl( 'handshake_verify_url' ), '/' ),
			$req->verification_code,
			$req->package_name,
			$req->pin
		);

		$rawResponse = $this->loadFS()->getUrlContent( $verifyURL );
		if ( empty( $rawResponse ) ) {
			return $this->setErrorResponse(
				sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $verifyURL ),
				9991
			);
		}

		$jsonResponse = \json_decode( \trim( $rawResponse ) );
		if ( !\is_object( $jsonResponse ) || empty( $jsonResponse->success ) ) {
			return $this->setErrorResponse(
				sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $verifyURL, $rawResponse ),
				9992
			);
		}

		$response->handshake = 'url';
		return $this->setSuccessResponse(); //just to be sure we proceed thereafter
	}

	/**
	 * @throws \Exception
	 */
	protected function verifyHmac() :bool {
		$req = $this->getRequestParams();

		$this->verificationWindowCheck();

		$verified = false;
		$hmac = $req->hmac_hash;
		if ( !empty( $req->pin ) && !empty( $hmac ) && \hash_equals( $this->mod->getPluginPin(), \hash( 'md5', $req->pin ) ) ) {
			$hashEquals = \hash_equals(
				$hmac,
				\hash_hmac(
					$req->hmac_algo,
					\implode( '', [
						$req->action,
						$req->verification_code,
						$req->verify_ts,
					] ),
					$req->pin
				)
			);
			if ( !$hashEquals ) {
				throw new \Exception( 'HMAC verification failed' );
			}
			$this->getStandardResponse()->handshake = 'hmac';
			$verified = true;
		}
		return $verified;
	}

	/**
	 * @throws \Exception
	 */
	protected function publicKeyVerify() :void {
		$req = $this->getRequestParams();
		$publicKey = $this->mod->getIcwpPublicKey();
		if ( empty( $publicKey ) || empty( $req->verification_code ) || empty( $req->opensig ) ) {
			throw new \Exception( 'Necessary components for public key-based verification are missing' );
		}

		if ( $this->isVerificationWindowRequired() ) {
			$this->verificationWindowCheck();
		}

		if ( !$this->loadEncryptProcessor()->getSupportsOpenSslSign() ) {
			throw new \Exception( 'OpenSSL Sign is not supported.' );
		}

		$response = $this->getStandardResponse();
		$response->openssl_verify = $this->loadEncryptProcessor()->verifySslSignature(
			$req->verification_code.( empty( $req->verify_ts ) ? '' : $req->verify_ts ),
			$req->opensig,
			$publicKey
		);
		if ( $response->openssl_verify !== 1 ) {
			throw new \Exception( 'OpenSSL Signature verification failed: '.$response->openssl_verify );
		}
		$response->handshake = 'openssl';
	}

	protected function preActionEnvironmentSetup() {
		$this->loadWP()->doBustCache();
//		@set_time_limit( $this->getRequestParams()->timeout );
	}

	/**
	 * @return bool
	 */
	protected function setWpEngineAuth() {
		if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) && $this->isLoggedInUser() ) {
			$oWpEngineCommon = WpeCommon::instance();
			$oWpEngineCommon->set_wpe_auth_cookie();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function setAuthorizedUser() {
		if ( !$this->isLoggedInUser() ) {
			$WPU = $this->loadWpUsers();
			$req = $this->getRequestParams();
			$wpUser = $req->wpadmin_user;
			if ( empty( $wpUser ) ) {

				if ( version_compare( $this->loadWP()->getWordpressVersion(), '3.1', '>=' ) ) {
					$aUserRecords = get_users( [
						'role'    => 'administrator',
						'number'  => 1,
						'orderby' => 'ID'
					] );
					if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
						$oUser = $aUserRecords[ 0 ];
					}
				}
				else {
					$oUser = $WPU->getUserById( 1 );
				}
				$wpUser = ( !empty( $oUser ) && is_a( $oUser, 'WP_User' ) ) ? $oUser->get( 'user_login' ) : 'admin';
			}

			if ( $WPU->setUserLoggedIn( $wpUser, (bool)$req->silent_login ) ) {
				$this->setLoggedInUser( $wpUser );
			}
		}
		return $this->isLoggedInUser();
	}

	/**
	 * Used by Execute and Retrieve
	 * @param string $installerFileToInclude
	 * @return LegacyApi\ApiResponse
	 */
	protected function runInstaller( $installerFileToInclude ) {
		$FS = $this->loadFS();

		$bIncludeSuccess = include_once( $installerFileToInclude );
		$FS->deleteFile( $installerFileToInclude );

		if ( !$bIncludeSuccess ) {
			return $this->setErrorResponse(
				'PHP failed to include the Installer file for execution.'
			);
		}

		if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
			$sErrorMessage = sprintf( 'Worpit_Package_Installer class does not exist after including file: "%s".', $installerFileToInclude );
			return $this->setErrorResponse(
				$sErrorMessage,
				-1 //TODO: Set a code
			);
		}

		$installer = new Worpit_Package_Installer();
		$installerResponse = $installer->run();

		$msg = !empty( $installerResponse[ 'message' ] ) ? $installerResponse[ 'message' ] : 'No message';
		$response = $installerResponse[ 'data' ] ?? [];
		if ( isset( $installerResponse[ 'success' ] ) && $installerResponse[ 'success' ] ) {
			return $this->setSuccessResponse(
				sprintf( 'Package Execution SUCCEEDED with message: "%s".', $msg ),
				0,
				$response
			);
		}
		else {
			return $this->setErrorResponse(
				sprintf( 'Package Execution FAILED with error message: "%s"', $msg ),
				-1, //TODO: Set a code
				$response
			);
		}
	}

	/**
	 * @param string $msg
	 * @param int    $code
	 * @param mixed  $data
	 */
	protected function setErrorResponse( $msg = '', $code = -1, $data = [] ) :LegacyApi\ApiResponse {
		$r = $this->getStandardResponse();
		$r->success = false;
		$r->error_message = $msg;
		$r->message = $msg;
		$r->code = $code;
		$r->data = $data;
		return $r;
	}

	/**
	 * @param string $msg
	 * @param int    $code
	 * @param mixed  $data
	 * @return LegacyApi\ApiResponse
	 */
	protected function setSuccessResponse( $msg = '', $code = 0, $data = [] ) :LegacyApi\ApiResponse {
		$r = $this->getStandardResponse();
		$r->success = true;
		$r->message = $msg;
		$r->code = $code;
		$r->data = empty( $data ) ? [ 'success' => 1 ] : $data;
		return $r;
	}

	public static function getStandardResponse() :LegacyApi\ApiResponse {
		return self::$actionResponse ??= new LegacyApi\ApiResponse();
	}

	/**
	 * @param string $sUser
	 * @return $this
	 */
	protected function setLoggedInUser( $sUser ) {
		$this->sLoggedInUser = $sUser;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getLoggedInUser() {
		$sLoggedInUser = $this->sLoggedInUser;
		if ( empty( $sLoggedInUser ) ) {
			$oWpUser = $this->loadWpUsers();
			if ( $oWpUser->isUserLoggedIn() && $oWpUser->isUserAdmin() ) {
				$sLoggedInUser = $oWpUser->getCurrentWpUser()->get( 'user_login' );
				$this->setLoggedInUser( $sLoggedInUser );
			}
		}
		return $this->sLoggedInUser;
	}

	protected function isLoggedInUser() :bool {
		return !empty( $this->getLoggedInUser() );
	}

	/**
	 * @throws Exception
	 */
	protected function verificationWindowCheck() :bool {
		$req = $this->getRequestParams();
		if ( empty( $req->verify_ts ) ) {
			throw new \Exception( 'Verification window check required, but verify_ts is missing.' );
		}
		if ( \time() - $req->verify_ts > 30 && ( ( new WorldTimeApi() )->current() - $req->verify_ts > 30 ) ) {
			throw new \Exception( 'Verification window has closed.' );
		}
		return true;
	}

	/**
	 * TODO: expand to all actions.
	 */
	protected function isVerificationWindowRequired() :bool {
		return \str_starts_with( $this->reqParams->action, 'worpdrive_' );
	}
}