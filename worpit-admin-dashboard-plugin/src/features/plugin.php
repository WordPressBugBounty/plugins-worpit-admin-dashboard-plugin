<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_FeatureHandler_Plugin extends ICWP_APP_FeatureHandler_Base {

	public const PAIRING_WINDOW_MINUTES = 15;
	public const API_STATUS_BOOTSTRAP_DENIED = 'BootstrapDenied';
	public const API_MESSAGE_BOOTSTRAP_DENIED = 'BootstrapDenied';
	public const API_MESSAGE_INVALID_BOOTSTRAP_CREDENTIALS = 'InvalidBootstrapCredentials';
	public const API_CODE_BOOTSTRAP_DENIED = 9811;

	/**
	 * @var LegacyApi\RequestParameters
	 */
	protected $reqParams;

	protected function doPostConstruction() {
		if ( is_admin() ) {
			add_action( 'wp_loaded', [ $this, 'doAutoRemoteSiteAdd' ] );
		}
		add_filter( 'plugin_action_links_'.self::con()->getPluginBaseFile(), [
			$this,
			'onWpPluginActionLinks'
		], 100, 1 );
	}

	/**
	 * @return bool
	 */
	protected function getIsShowMarketing() :bool {
		return $this->getInstallationDays() > 1
			   && !ICWP_Plugin::getController()->loadCorePluginFeatureHandler()->getIsSiteLinked();
	}

	protected function getInstallationDays() :int {
		$installedFor = $this->getPluginInstallationTime();
		return empty( $installedFor ) ? 0 : (int)\round( ( $this->loadDP()->time() - $installedFor )/DAY_IN_SECONDS );
	}

	public function displayFeatureConfigPage() {
		$bootstrapWindowExpiresAt = $this->getBootstrapWindowExpiresAt();
		$this->display(
			[
				'aPluginLabels'             => self::con()->getPluginLabels(),
				'sAuthKey'                  => $this->getPluginAuthKey(),
				'sAssignedTo'               => $this->getAssignedTo(),
				'bAssigned'                 => $this->getAssigned(),
				'bIsLinked'                 => $this->getIsSiteLinked(),
				'bCanHandshake'             => $this->getCanHandshake(),
				'bBootstrapWindowOpen'      => $this->isBootstrapWindowOpen(),
				'nBootstrapWindowExpiresAt' => $bootstrapWindowExpiresAt,
				'sBootstrapWindowExpiresAt' => empty( $bootstrapWindowExpiresAt ) ? '' : $this->loadWP()
																							  ->getTimeStringForDisplay( $bootstrapWindowExpiresAt ),
				'sExtraContent'             => apply_filters( self::con()->doPluginPrefix( 'main_extracontent' ), '' ),
			],
			'feature-plugin'
		);
	}

	/**
	 * @param array $aActions
	 * @return array
	 */
	public function onWpPluginActionLinks( $aActions ) {
		if ( $this->getIsSiteLinked() && isset( $aActions[ 'deactivate' ] ) ) {
			$sJsConfirmCode = '" onClick="return confirm(\'WARNING: If you have WorpDrive automatic backups active on this site, backups will also stop running. Are you absolutely sure?\');" >';
			$aActions[ 'deactivate' ] = preg_replace( '#"\s*>#i', $sJsConfirmCode, $aActions[ 'deactivate' ], 1 );
		}
		return $aActions;
	}

	/**
	 * @param string $sMessage
	 */
	public function doAddAdminFeedback( $sMessage ) {
		$aFeedback = $this->getOpt( 'feedback_admin_notice', [] );
		$aFeedback[] = $sMessage;
		$this->setOpt( 'feedback_admin_notice', $aFeedback );
	}

	/**
	 * @param bool $doVerify
	 * @return bool
	 */
	public function getCanHandshake( $doVerify = false ) {

		if ( !$doVerify ) { // we always verify can handshake at least once every 24hrs
			$sinceLastHandshakeCheck = $this->loadDP()
											->time() - $this->getOpt( 'time_last_check_can_handshake', 0 );
			if ( $sinceLastHandshakeCheck > DAY_IN_SECONDS ) {
				$doVerify = true;
			}
		}

		if ( $doVerify ) {
			$canHandshake = apply_filters( self::con()
											   ->doPluginPrefix( 'verify_site_can_handshake' ), false );
			$this->setOpt( 'can_handshake', $canHandshake ? 'Y' : 'N' );
		}
		return $this->getOptIs( 'can_handshake', 'Y' );
	}

	/**
	 * @return bool
	 */
	public function setCanHandshake() {
		return $this->getCanHandshake( true );
	}

	public function getIsSiteLinked() :bool {
		return $this->getAssigned() && is_email( $this->getAssignedTo() );
	}

	public function getBootstrapWindowExpiresAt() :int {
		return (int)$this->getOpt( 'bootstrap_window_expires_at', 0 );
	}

	public function isBootstrapWindowOpen() :bool {
		return $this->getBootstrapWindowExpiresAt() > $this->loadDP()->time();
	}

	public function openBootstrapWindow() :self {
		$this->setOpt( 'key', $this->loadDP()->GenerateRandomString( 24 ) );
		$this->setOpt( 'bootstrap_window_expires_at', $this->loadDP()
														   ->time() + ( self::PAIRING_WINDOW_MINUTES*MINUTE_IN_SECONDS ) );
		return $this;
	}

	public function closeBootstrapWindow() :self {
		$this->setOpt( 'bootstrap_window_expires_at', 0 );
		return $this;
	}

	public function doExtraSubmitProcessing() {
		$DP = $this->loadDP();

		if ( $DP->FetchPost( self::con()->doPluginOptionPrefix( 'reset_plugin' ) ) ) {
			$sTo = $this->getAssignedTo();
			$sKey = $this->getPluginAuthKey();
			$sPin = $this->getPluginPin();

			if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
				$aParts = [ urlencode( $sTo ), $sKey, $sPin ];
				$this->loadFS()->getUrl( $this->getAppUrl( 'reset_site_url' ).implode( '/', $aParts ) );
			}
			$this->setOpt( 'key', '' );
			$this->setPluginPin( '' );
			$this->setAssignedAccount( '' );
			$this->closeBootstrapWindow();
			return;
		}

		if ( $DP->FetchPost( self::con()->doPluginOptionPrefix( 'open_pairing_window' ) ) ) {
			$this->openBootstrapWindow();
			$this->doAddAdminFeedback( sprintf( '%s pairing window opened for 15 minutes.', self::con()
																								->getHumanName() ) );
			return;
		}

		//Clicked the button to remotely add siteself::con()->doPluginOptionPrefix( 'reset_plugin' )
		if ( $DP->FetchPost( self::con()->doPluginOptionPrefix( 'remotely_add_site_submit' ) ) ) {
			$auth = $DP->FetchPost( 'account_auth_key' );
			$email = $DP->FetchPost( 'account_email_address' );
			if ( $auth && $email ) {
				if ( $this->doRemoteAddSiteLink( $auth, $email ) ) {
					$this->doAddAdminFeedback( sprintf( ( '%s Plugin options updated successfully.' ),
						self::con()->getHumanName() ) );
				}
			}
			$this->doAddAdminFeedback( sprintf( ( '%s Site NOT added.' ), self::con()->getHumanName() ) );
			return;
		}
		$this->doAddAdminFeedback( sprintf( ( '%s Plugin options updated successfully.' ), self::con()
																							   ->getHumanName() ) );
	}

	/**
	 * This function always returns false, however the return is never actually used just yet.
	 *
	 * @param string $authKey
	 * @param string $email
	 * @return bool
	 */
	private function doRemoteAddSiteLink( $authKey, $email ) {
		$authKey = \trim( $authKey );
		$email = \trim( $email );
		if ( !$this->getIsSiteLinked() && \strlen( $authKey ) === 32 && is_email( $email ) ) {
			$this->openBootstrapWindow();
			$this->savePluginOptions();
			return $this->loadFS()->postUrl( $this->getAppUrl( 'remote_add_site_url' ), [
				'body' => [
					'wordpress_url'         => home_url(),
					'plugin_url'            => self::con()->getPluginUrl(),
					'account_email_address' => $email,
					'account_auth_key'      => $authKey,
					'plugin_key'            => $this->getPluginAuthKey()
				]
			] );
		}
		return false;
	}

	/**
	 * reads the auto_add.php file (yaml) to an api key and email and automatically adds the site to the account.
	 */
	public function doAutoRemoteSiteAdd() {
		$autoAddPath = self::con()->getRootDir().'auto_add.php';
		if ( $this->getIsSiteLinked() || !$this->loadFS()->isFile( $autoAddPath ) ) {
			return;
		}
		$content = $this->loadDP()->readFileContentsUsingInclude( $autoAddPath );
		$this->loadFS()->deleteFile( $autoAddPath );
		if ( !empty( $content ) ) {
			$dec = \json_decode( $content, true );
			$this->doRemoteAddSiteLink( $dec[ 'api-key' ] ?? '', $dec[ 'email' ] ?? '' );
		}
	}

	/**
	 * @return array
	 */
	public function getActivePluginFeatures() {
		$aActiveFeatures = $this->opts()->getRawData_SingleOption( 'active_plugin_features' );
		$aPluginFeatures = [];
		if ( empty( $aActiveFeatures[ 'value' ] ) || !is_array( $aActiveFeatures[ 'value' ] ) ) {
			return $aPluginFeatures;
		}

		foreach ( $aActiveFeatures[ 'value' ] as $nPosition => $aFeature ) {
			if ( isset( $aFeature[ 'hidden' ] ) && $aFeature[ 'hidden' ] ) {
				continue;
			}
			$aPluginFeatures[ $aFeature[ 'slug' ] ] = $aFeature;
		}
		return $aPluginFeatures;
	}

	public function getAssigned() :bool {
		return $this->getOptIs( 'assigned', 'Y' );
	}

	public function getAssignedTo() :string {
		return (string)$this->getOpt( 'assigned_to', '' );
	}

	public function getIcwpPublicKey() :string {
		$key = $this->getDefinition( 'icwp_public_key' );
		return empty( $key ) ? '' : (string)\base64_decode( $key );
	}

	public function getPluginAuthKey() :string {
		$auth = $this->getOpt( 'key' );
		if ( empty( $auth ) ) {
			$auth = $this->loadDP()->GenerateRandomString( 24 );
			$this->setOpt( 'key', $auth );
		}
		return (string)$auth;
	}

	public function isValidBootstrapRequestKey( string $requestKey ) :bool {
		$requestKey = \trim( $requestKey );
		return !empty( $requestKey ) && \hash_equals( $this->getPluginAuthKey(), $requestKey );
	}

	public function getPluginPin() :string {
		return (string)$this->getOpt( 'pin' );
	}

	public function getPermittedApiChannels() :array {
		return $this->getDefinition( 'permitted_api_channels' );
	}

	public function getDefinedServiceIps( int $ipVersion = 4 ) :array {
		$lists = $this->getDefinition( 'service_ip_addresses' );
		$key = $ipVersion === 6 ? 'ipv6' : 'ipv4';
		return isset( $lists[ $key ][ 'valid' ] ) && \is_array( $lists[ $key ][ 'valid' ] ) ? $lists[ $key ][ 'valid' ] : [];
	}

	public function getServiceIps( int $ipVersion = 4 ) :array {
		$v = \in_array( $ipVersion, [ 4, 6 ], true ) ? $ipVersion : 4;
		$result = apply_filters(
			self::con()->doPluginPrefix( 'get_service_ips_v'.$v ),
			$this->getDefinedServiceIps( $v )
		);
		return \is_array( $result ) ? $result : [];
	}

	public function isServiceIp( string $ip ) :bool {
		$ip = \trim( $ip );
		$version = (int)$this->loadDP()->getIpAddressVersion( $ip );
		return !empty( $ip ) &&
			   (
				   ( $version === 6 && \in_array( $ip, $this->getServiceIps( 6 ), true ) )
				   || ( $version === 4 && \in_array( $ip, $this->getServiceIps( 4 ), true ) )
			   );
	}

	public function isUnlinkedBootstrapPermitted() :bool {
		if ( $this->getIsSiteLinked() ) {
			return true;
		}
		$ip = \trim( (string)$this->loadDP()->FetchServer( 'REMOTE_ADDR' ) );
		return $this->isBootstrapWindowOpen() && $this->isServiceIp( $ip );
	}

	public function getBootstrapAuthKeyForResponse() :string {
		return $this->getIsSiteLinked() || !$this->isUnlinkedBootstrapPermitted() ? '' : $this->getPluginAuthKey();
	}

	public function getSupportedInternalApiAction() :array {
		return $this->getDefinition( 'internal_api_supported_actions' );
	}

	/**
	 * @return array
	 */
	public function getSupportedModules() {
		return $this->getDefinition( 'supported_modules' );
	}

	/**
	 * No checking or validation done for email.  If it's empty, the site is unassigned.
	 *
	 * @param $sAccountEmail
	 */
	public function setAssignedAccount( $sAccountEmail = null ) {
		if ( !empty( $sAccountEmail ) && is_email( $sAccountEmail ) ) {
			$this->setOpt( 'assigned', 'Y' );
			$this->setOpt( 'assigned_to', $sAccountEmail );
		}
		else {
			$this->setOpt( 'assigned', 'N' );
			$this->setOpt( 'assigned_to', '' );
		}
	}

	/**
	 * The PIN should be passed here without any pre-processing (such as MD5)
	 *
	 * @param $rawPin
	 * @return $this
	 */
	public function setPluginPin( $rawPin ) {
		$trimmed = \trim( (string)$rawPin );
		$this->setOpt( 'pin', empty( $trimmed ) ? '' : \md5( $trimmed ) );
		return $this;
	}

	/**
	 * @param array $aSummaryData
	 * @return array
	 */
	public function filter_getFeatureSummaryData( $aSummaryData ) {
		return $aSummaryData;
	}

	/**
	 * This is the point where you would want to do any options verification
	 */
	protected function doPrePluginOptionsSave() {
		if ( $this->getOpt( 'activated_at', 0 ) <= 0 ) {
			$this->setOpt( 'activated_at', $this->loadDP()->time() );
		}
		if ( $this->getOpt( 'installation_time', 0 ) <= 0 ) {
			$this->setOpt( 'installation_time', $this->loadDP()->time() );
		}
		$this->setOpt( 'installed_version', self::con()->getVersion() );
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getAppUrl( $key ) {
		$urls = $this->getDefinition( 'urls' );
		return empty( $urls[ $key ] ) ? '' : $urls[ $key ];
	}
}
