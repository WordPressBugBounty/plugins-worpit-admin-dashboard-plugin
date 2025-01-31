<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_FeatureHandler_Plugin extends ICWP_APP_FeatureHandler_Base {

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
		$this->display(
			[
				'aPluginLabels' => self::con()->getPluginLabels(),
				'sAuthKey'      => $this->getPluginAuthKey(),
				'sAssignedTo'   => $this->getAssignedTo(),
				'bAssigned'     => $this->getAssigned(),
				'bIsLinked'     => $this->getIsSiteLinked(),
				'bCanHandshake' => $this->getCanHandshake(),
				'sExtraContent' => apply_filters( self::con()->doPluginPrefix( 'main_extracontent' ), '' ),
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

	/**
	 * @return bool
	 */
	public function getAssigned() {
		return $this->getOptIs( 'assigned', 'Y' );
	}

	/**
	 * @return string (email)
	 */
	public function getAssignedTo() {
		return $this->getOpt( 'assigned_to', '' );
	}

	/**
	 * @return string (URL)
	 */
	public function getHelpdeskSsoUrl() {
		return $this->getOpt( 'helpdesk_sso_url', '' );
	}

	/**
	 * @return string
	 */
	public function getIcwpPublicKey() {
		$sKey = $this->getDefinition( 'icwp_public_key' );
		return empty( $sKey ) ? '' : base64_decode( $sKey );
	}

	/**
	 * @return string
	 */
	public function getPluginAuthKey() {
		$sOptionKey = 'key';
		$sAuthKey = $this->getOpt( $sOptionKey );
		if ( empty( $sAuthKey ) ) {
			$sAuthKey = $this->loadDP()->GenerateRandomString( 24, 7 );
			$this->setOpt( $sOptionKey, $sAuthKey );
		}
		return $sAuthKey;
	}

	/**
	 * @return string
	 */
	public function getPluginPin() {
		return $this->getOpt( 'pin' );
	}

	public function getPermittedApiChannels() :array {
		return $this->getDefinition( 'permitted_api_channels' );
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
	 * @param string $sEmail
	 * @return $this
	 */
	public function setAssignedTo( $sEmail ) {
		$this->setOpt( 'assigned_to', $sEmail );
		return $this;
	}

	/**
	 * @param string $sUrl
	 * @return $this
	 */
	public function setHelpdeskSsoUrl( $sUrl ) {
		$this->setOpt( 'helpdesk_sso_url', $sUrl );
		return $this;
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

	public function getRequestParams() :LegacyApi\RequestParameters {
		return $this->reqParams ?? $this->reqParams = new LegacyApi\RequestParameters(
			$this->loadDP()->FetchGet( 'reqpars', [] ),
			$this->loadDP()->FetchPost( 'reqpars', [] )
		);
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

	/**
	 * @param bool $bDoHidePlugin
	 * @return bool
	 * @deprecated 4.5
	 */
	public function getIfHidePlugin( $bDoHidePlugin ) {
		return false;
	}
}