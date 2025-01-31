<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_Processor_Plugin extends ICWP_APP_Processor_BaseApp {

	public function run() {
		$params = $this->getRequestParams();

		add_filter( self::con()->doPluginPrefix( 'get_service_ips_v4' ), [ $this, 'getServiceIpAddressesV4' ] );
		add_filter( self::con()->doPluginPrefix( 'get_service_ips_v6' ), [ $this, 'getServiceIpAddressesV6' ] );

		add_filter( self::con()->doPluginPrefix( 'verify_site_can_handshake' ), [ $this, 'doVerifyCanHandshake' ] );

		add_action( 'init', [ $this, 'getPluginUrl' ], -100000 );

		$apiHook = $params->getApiHook();
		if ( $params->worpit_link ) {
			if ( $apiHook == 'immediate' ) {
				$this->doApiLinkSite();
			}
			else {
				add_action( $apiHook, [ $this, 'doApiLinkSite' ], $params->getApiHookPriority() );
			}
		}
		elseif ( $params->worpit_api || $params->icwpapi ) {
			if ( $apiHook == 'immediate' ) {
				$this->doApiAction();
			}
			else {
				add_action( $apiHook, [ $this, 'doApiAction' ], $params->getApiHookPriority() );
			}
		}
	}

	public function getPluginUrl() {
		if ( $this->loadDP()->FetchRequest( 'geticwppluginurl' ) == 1 ) {
			$this->returnIcwpPluginUrl();
		}
	}

	public function getServiceIpAddressesV4() :array {
		return $this->getValidServiceIps();
	}

	public function getServiceIpAddressesV6() :array {
		return $this->getValidServiceIps( 'ipv6' );
	}

	protected function getValidServiceIps( string $ips = 'ipv4' ) :array {
		$lists = $this->mod->getDefinition( 'service_ip_addresses' );
		if ( isset( $lists[ $ips ][ 'valid' ] ) && \is_array( $lists[ $ips ] ) && \is_array( $lists[ $ips ][ 'valid' ] ) ) {
			return $lists[ $ips ][ 'valid' ];
		}
		return [];
	}

	public function doVerifyCanHandshake() :bool {
		$this->mod->setOpt( 'time_last_check_can_handshake', $this->loadDP()->time() );

		// First simply check SSL support
		if ( $this->loadEncryptProcessor()->getSupportsOpenSslSign() ) {
			return true;
		}

		$response = $this->loadFS()->getUrlContent( $this->mod->getAppUrl( 'handshake_verify_test_url' ), [
			'timeout'     => 20,
			'redirection' => 20,
			'sslverify'   => true //this is default, but just to make sure.
		] );
		if ( !$response ) {
			return false;
		}

		$jsonResponse = \json_decode( \trim( $response ) );
		return \is_object( $jsonResponse ) && !empty( $jsonResponse->success );
	}

	/**
	 * @uses die()
	 */
	public function doApiLinkSite() {
		require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
		$this->sendApiResponse( ( new \ICWP_APP_Processor_Plugin_SiteLink( $this->mod ) )->run() );
		die();
	}

	/**
	 * If any of the conditions are met and our plugin executes either the transport or link
	 * handlers, then all execution will end
	 * @return void
	 * @uses die
	 */
	public function doApiAction() {
		require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
		$class = $this->enum()[ $this->getApiChannel() ] ?? \ICWP_APP_Processor_Plugin_Api_Index::class;
		$this->sendApiResponse( ( new $class( $this->mod ) )->run(), (bool)$this->getRequestParams()->icwpenc );
		die();
	}

	/**
	 * @return class-string<\ICWP_APP_Processor_Plugin_Api>[]
	 */
	private function enum() :array {
		return [
			'auth'     => \ICWP_APP_Processor_Plugin_Api_Auth::class,
			'retrieve' => \ICWP_APP_Processor_Plugin_Api_Retrieve::class,
			'internal' => \ICWP_APP_Processor_Plugin_Api_Internal::class,
			'status'   => \ICWP_APP_Processor_Plugin_Api_Status::class,
			'login'    => \ICWP_APP_Processor_Plugin_Api_Login::class,
			'download' => LegacyApi\Channel\Download::class
		];
	}

	protected function getApiChannel() :string {
		$params = $this->getRequestParams();
		return \in_array( $params->m, $this->mod->getPermittedApiChannels() ) ? $params->m : 'index';
	}

	/**
	 * @return void
	 */
	protected function returnIcwpPluginUrl() {
		( new LegacyApi\Response\SendAppApiResponse() )->flush(
			[
				'content'  => self::con()->getPluginUrl(),
				'encoding' => 'none',
				'version'  => $this->mod->getVersion(),
				'auth_key' => $this->mod->getIsSiteLinked() ? '' : $this->mod->getPluginAuthKey()
			],
			false
		);
	}

	protected function sendApiResponse( LegacyApi\ApiResponse $response, bool $encrypt = false ) {
		$response->authenticated = $this->loadWpUsers()->isUserLoggedIn();

		$toSend = clone $response;

		if ( $encrypt && !empty( $toSend->data ) ) {
			$encryptedResult = $this->loadEncryptProcessor()->sealData(
				$toSend->data,
				$this->mod->getIcwpPublicKey()
			);

			if ( $encryptedResult->success ) {
				$toSend->data = [
					'is_encrypted' => 1,
					'password'     => $encryptedResult->encrypted_password,
					'sealed_data'  => $encryptedResult->encrypted_data
				];
			}
		}

		( new LegacyApi\Response\SendAppApiResponse() )->flush(
			[
				'content'  => \base64_encode( $this->loadDP()->encodeJson( $toSend->getResponsePackage() ) ),
				'encoding' => 'json',
				'version'  => $this->mod->getVersion(),
				'auth_key' => $this->mod->getIsSiteLinked() ? '' : $this->mod->getPluginAuthKey()
			],
			false
		);
	}
}