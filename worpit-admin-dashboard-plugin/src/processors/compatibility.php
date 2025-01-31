<?php

class ICWP_APP_Processor_Compatibility extends ICWP_APP_Processor_BaseApp {

	public function run() {
		$this->setupWhitelists();
		// Only when the request comes from iControlWP.
		if ( $this->getIsRequestFromServiceIp() ) {
			$this->unhookRedirection();
			$this->unhookMaintenanceModePlugins();
			$this->unhookSecurityPlugins();
		}
	}

	public function getServiceIps( int $ipVersion = 4 ) :array {
		$v = \in_array( $ipVersion, [ 4, 6 ] ) ? $ipVersion : 4;
		$aResult = apply_filters( self::con()->doPluginPrefix( 'get_service_ips_v'.$v ), [] );
		return \is_array( $aResult ) ? $aResult : [];
	}

	protected function getIsRequestFromServiceIp() :bool {
		$ip = $this->loadDP()->getVisitorIpAddress();
		return \in_array( $ip, $this->getServiceIps() ) || \in_array( $ip, $this->getServiceIps( 6 ) );
	}

	/**
	 * Should be hooked to 'plugins_loaded' and will add iCWP IPs automatically where possible
	 * to the whitelists of certain plugins that might otherwise block us.
	 */
	public function setupWhitelists() {
		$this->addToWordfence();
		$this->addToBadBehaviour();
	}

	protected function addToWordfence() {
		if ( !\class_exists( 'wordfence' ) || !\method_exists( 'wordfence', 'whitelistIP' ) ) {
			return;
		}
		try {
			foreach ( $this->getServiceIps() as $sServiceIp ) {
				if ( !empty( $sServiceIp ) && \is_string( $sServiceIp ) ) {
					wordfence::whitelistIP( $sServiceIp );
				}
			}
		}
		catch ( \Exception $e ) {
		}
	}

	protected function addToBadBehaviour() {
		if ( !( \defined( 'BB2_VERSION' ) && \defined( 'BB2_CORE' ) && \function_exists( 'bb2_read_whitelist' ) ) ) {
			return;
		}

		$bAdded = false;
		$aServiceIps = $this->getServiceIps();
		$sBbIpWhitelist = bb2_read_whitelist();
		if ( empty( $sBbIpWhitelist[ 'ip' ] ) || !\is_array( $sBbIpWhitelist[ 'ip' ] ) ) {
			$sBbIpWhitelist[ 'ip' ] = $aServiceIps;
			$bAdded = true;
		}
		else {
			foreach ( $aServiceIps as $sServiceIp ) {
				if ( !in_array( $sServiceIp, $sBbIpWhitelist[ 'ip' ] ) ) {
					$sBbIpWhitelist[ 'ip' ][] = $sServiceIp;
					$bAdded = true;
				}
			}
		}

		if ( $bAdded ) {
			update_option( 'bad_behavior_whitelist', $sBbIpWhitelist );
		}
	}

	/**
	 * Removes any interruption from Maintenance Mode plugins while iControlWP is executing a package.
	 * @return void
	 */
	public function unhookMaintenanceModePlugins() {
		//ET Anticipate Maintenance Plugin from elegant themes
		if ( \class_exists( 'ET_Anticipate' ) ) {
			remove_action( 'init', 'ET_Anticipate_Init', 5 );
		}

		if ( \class_exists( 'tf_maintenance', false ) ) {
			remove_action( 'init', 'tf_maintenance_Init', 5 );
		}

		// WP Maintenance Mode Plugin ( https://wordpress.org/plugins/wp-maintenance-mode/ )
		if ( \class_exists( 'WP_Maintenance_Mode', false ) && \method_exists( 'WP_Maintenance_Mode', 'get_instance' ) ) {
			remove_action( 'init', [ WP_Maintenance_Mode::get_instance(), 'init' ] );
		}

		//underConstruction plugin
		global $underConstructionPlugin;
		if ( class_exists( 'underConstruction', false ) && isset( $underConstructionPlugin ) && \is_object( $underConstructionPlugin ) ) {
			remove_action( 'template_redirect', [ $underConstructionPlugin, 'uc_overrideWP' ] );
			remove_action( 'admin_init', [ $underConstructionPlugin, 'uc_admin_override_WP' ] );
			remove_action( 'wp_login', [ $underConstructionPlugin, 'uc_admin_override_WP' ] );
		}

		//Ultimate Maintenance Mode plugin
		global $seedprod_umm;
		if ( \class_exists( 'SeedProd_Ultimate_Maintenance_Mode' ) && isset( $seedprod_umm ) && \is_object( $seedprod_umm ) ) {
			remove_action( 'template_redirect', [ $seedprod_umm, 'render_maintenancemode_page' ] );
		}

		global $seedprod_comingsoon;
		if ( \class_exists( 'SeedProd_Ultimate_Coming_Soon_Page', false ) && isset( $seedprod_comingsoon ) && \is_object( $seedprod_comingsoon ) ) {
			remove_action( 'template_redirect', [ $seedprod_comingsoon, 'render_comingsoon_page' ] );
			remove_action( 'template_redirect', [ $seedprod_comingsoon, 'render_comingsoon_page' ], 9 );
		}
	}

	/**
	 * These should only run when it's an iControlWP request.
	 */
	protected function unhookSecurityPlugins() {
		$this->removeSecureWpHooks();
		$this->removeWpSpamShield();
		$this->removeAiowpsHooks(); //wp-security-core.php line 25
		$this->removeBetterWpSecurityHooks();
		$this->removeWordfence();
		$this->removeSucuri();
	}

	protected function removeWordfence() {
		remove_action( 'wp_login', 'wordfence::loginAction' );
	}

	protected function removeSucuri() {
		remove_action( 'wp_login', 'SucuriScanHook::hook_wp_login' );
	}

	protected function unhookRedirection() {
		if ( class_exists( 'Redirection', false ) && class_exists( 'WordPress_Module', false ) ) {
			global $redirection;
			if ( is_object( $redirection ) && isset( $redirection->wp ) && is_object( $redirection->wp ) ) {
				remove_action( 'init', [ $redirection->wp, 'init' ] );
				remove_action( 'send_headers', [ $redirection->wp, 'send_headers' ] );
				remove_action( 'permalink_redirect_skip', [ $redirection->wp, 'permalink_redirect_skip' ] );
				remove_action( 'wp_redirect', [ $redirection->wp, 'wp_redirect' ], 1 );
			}
		}
	}

	/**
	 * Note: This only ever gets run during an iCWP request.
	 *
	 * Remove actions setup by All In One WP Security plugin that interferes with iControlWP packages.
	 * @return void
	 */
	protected function removeAiowpsHooks() {
		if ( class_exists( 'AIO_WP_Security' ) && isset( $GLOBALS[ 'aio_wp_security' ] ) && is_object( $GLOBALS[ 'aio_wp_security' ] ) ) {
			remove_action( 'init', [ $GLOBALS[ 'aio_wp_security' ], 'wp_security_plugin_init' ], 0 );
		}
	}

	/**
	 * Note: This only ever gets run during an iCWP request.
	 *
	 * Remove actions setup by Secure WP plugin that interfere with Worpit synchronizing packages.
	 * @return void
	 */
	protected function removeSecureWpHooks() {
		global $SecureWP;
		if ( class_exists( 'SecureWP' ) && isset( $SecureWP ) && is_object( $SecureWP ) ) {
			remove_action( 'init', [ $SecureWP, 'replace_wp_version' ], 1 );
			remove_action( 'init', [ $SecureWP, 'remove_core_update' ], 1 );
			remove_action( 'init', [ $SecureWP, 'remove_plugin_update' ], 1 );
			remove_action( 'init', [ $SecureWP, 'remove_theme_update' ], 1 );
			remove_action( 'init', [ $SecureWP, 'remove_wp_version_on_admin' ], 1 );
		}
	}

	/**
	 * Note: This only ever gets run during an iCWP request.
	 * @return void
	 */
	protected function removeWpSpamShield() {
		add_filter( 'wpss_misc_form_spam_check_bypass', '__return_true', 100 );
		/* if ( function_exists( 'rs_wpss_misc_form_spam_check' ) ) {
			remove_action( 'init', 'rs_wpss_misc_form_spam_check', 2 );
		}*/
	}

	/**
	 * Note: This only ever gets run during an iCWP request.
	 *
	 * Remove actions setup by Better WP Security plugin that interfere with iControlWP synchronizing packages.
	 * Check secure.php for changes to these hooks.
	 * @return void
	 */
	protected function removeBetterWpSecurityHooks() {
		global $bwps, $bwpsoptions;

		if ( class_exists( 'bwps_secure' ) && isset( $bwps ) && is_object( $bwps ) ) {
			remove_action( 'plugins_loaded', [ $bwps, 'randomVersion' ] );
			remove_action( 'plugins_loaded', [ $bwps, 'pluginupdates' ] );
			remove_action( 'plugins_loaded', [ $bwps, 'themeupdates' ] );
			remove_action( 'plugins_loaded', [ $bwps, 'coreupdates' ] );
			remove_action( 'plugins_loaded', [ $bwps, 'siteinit' ] );
		}

		// Adds our IP addresses to the BWPS whitelist
		if ( \is_array( $bwpsoptions ) ) {
			$sServiceIps = \implode( "\n", $this->getServiceIps( 4 ) );
			if ( !isset( $bwpsoptions[ 'id_whitelist' ] ) || strlen( $bwpsoptions[ 'id_whitelist' ] ) == 0 ) {
				$bwpsoptions[ 'id_whitelist' ] = $sServiceIps;
			}
			elseif ( strpos( $bwpsoptions[ 'id_whitelist' ], $sServiceIps ) === false ) {
				$bwpsoptions[ 'id_whitelist' ] .= "\n".$sServiceIps;
			}
		}
	}
}