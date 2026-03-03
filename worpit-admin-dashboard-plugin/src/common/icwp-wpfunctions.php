<?php

class ICWP_APP_WpFunctions extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpFunctions
	 */
	protected static $I = null;

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	/**
	 * @var string
	 */
	protected $sWpVersion;

	public function __construct() {
	}

	/**
	 * @return bool
	 */
	public function getIsRunningAutomaticUpdates() {
		return get_option( 'auto_updater.lock' ) ? true : false;
	}

	/**
	 * Clears any WordPress caches
	 */
	public function doBustCache() {
		global $_wp_using_ext_object_cache, $wp_object_cache;
		$_wp_using_ext_object_cache = false;
		if ( !empty( $wp_object_cache ) ) {
			@$wp_object_cache->flush();
		}
	}

	/**
	 * @param bool $bRemoveSchema
	 * @return string
	 */
	public function getHomeUrl( $bRemoveSchema = false ) {
		$sUrl = home_url();
		if ( empty( $sUrl ) ) {
			remove_all_filters( 'home_url' );
			$sUrl = home_url();
		}
		if ( $bRemoveSchema ) {
			$sUrl = preg_replace( '#^((http|https):)?\/\/#i', '', $sUrl );
		}
		return $sUrl;
	}

	/**
	 * @param bool $bRemoveSchema
	 * @return string
	 */
	public function getSiteUrl( $bRemoveSchema = false ) {
		$url = site_url();
		if ( empty( $url ) ) {
			remove_all_filters( 'site_url' );
			$url = home_url();
		}
		if ( $bRemoveSchema ) {
			$url = \preg_replace( '#^((http|https):)?//#i', '', $url );
		}
		return $url;
	}

	/**
	 * @param bool $bForChecksums
	 * @return string
	 */
	public function getLocale( $bForChecksums = false ) {
		$sLocale = get_locale();
		if ( $bForChecksums ) {
			global $wp_local_package;
			$sLocale = empty( $wp_local_package ) ? 'en_US' : $wp_local_package;
		}
		return $sLocale;
	}

	/**
	 * @return array
	 */
	public function getPlugins() {
		if ( !\function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH.'wp-admin/includes/plugin.php' );
		}
		return \function_exists( 'get_plugins' ) ? get_plugins() : [];
	}

	/**
	 * @param string $sPluginFile
	 * @return stdClass|null
	 */
	public function getPluginUpdateInfo( $sPluginFile ) {
		$aUpdates = $this->getWordpressUpdates();
		return ( !empty( $aUpdates ) && isset( $aUpdates[ $sPluginFile ] ) ) ? $aUpdates[ $sPluginFile ] : null;
	}

	/**
	 * @param string $sPluginFile
	 * @return string
	 */
	public function getPluginUpdateNewVersion( $sPluginFile ) {
		$oInfo = $this->getPluginUpdateInfo( $sPluginFile );
		return ( !is_null( $oInfo ) && isset( $oInfo->new_version ) ) ? $oInfo->new_version : '';
	}

	/**
	 * @param stdClass|string $mItem
	 * @param string          $sContext from plugin|theme
	 * @return string
	 */
	public function getFileFromAutomaticUpdateItem( $mItem, $sContext = 'plugin' ) {
		if ( is_object( $mItem ) && isset( $mItem->{$sContext} ) ) { // WP 3.8.2+
			$mItem = $mItem->{$sContext};
		}
		elseif ( !is_string( $mItem ) ) { // WP pre-3.8.2
			$mItem = '';
		}
		return $mItem;
	}

	/**
	 * @param string $sType - plugins, themes
	 * @return array
	 */
	public function getWordpressUpdates( $sType = 'plugins' ) {
		$oCurrent = $this->getTransient( 'update_'.$sType );
		return ( is_object( $oCurrent ) && isset( $oCurrent->response ) ) ? $oCurrent->response : [];
	}

	/**
	 * @param string $sKey
	 * @return mixed
	 */
	public function getTransient( $sKey ) {

		// TODO: Handle multisite

		if ( version_compare( $this->getWordpressVersion(), '2.7.9', '<=' ) ) {
			return get_option( $sKey );
		}

		if ( function_exists( 'get_site_transient' ) ) {
			$mResult = get_site_transient( $sKey );
			if ( empty( $mResult ) ) {
				remove_all_filters( 'pre_site_transient_'.$sKey );
				$mResult = get_site_transient( $sKey );
			}
			return $mResult;
		}

		if ( version_compare( $this->getWordpressVersion(), '2.9.9', '<=' ) ) {
			return apply_filters( 'transient_'.$sKey, get_option( '_transient_'.$sKey ) );
		}

		return apply_filters( 'site_transient_'.$sKey, get_option( '_site_transient_'.$sKey ) );
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mValue
	 * @param int    $nExpire
	 */
	public function setTransient( $sKey, $mValue, $nExpire = 0 ) {
		set_site_transient( $sKey, $mValue, $nExpire );
	}

	/**
	 * @param $sKey
	 *
	 * @return bool
	 */
	public function deleteTransient( $sKey ) {

		if ( version_compare( $this->getWordpressVersion(), '2.7.9', '<=' ) ) {
			return delete_option( $sKey );
		}

		if ( function_exists( 'delete_site_transient' ) ) {
			return delete_site_transient( $sKey );
		}

		if ( version_compare( $this->getWordpressVersion(), '2.9.9', '<=' ) ) {
			return delete_option( '_transient_'.$sKey );
		}

		return delete_option( '_site_transient_'.$sKey );
	}

	/**
	 * @param bool $sForceUpdate
	 * @return string
	 */
	public function getWordpressVersion( $sForceUpdate = false ) {

		if ( $sForceUpdate || empty( $this->sWpVersion ) ) {
			$sVersionFile = ABSPATH.WPINC.'/version.php';
			$sVersionContents = file_get_contents( $sVersionFile );

			if ( preg_match( '/wp_version\s=\s\'([^(\'|")]+)\'/i', $sVersionContents, $aMatches ) ) {
				$this->sWpVersion = $aMatches[ 1 ];
			}
			else {
				global $wp_version;
				$this->sWpVersion = $wp_version;
			}
		}
		return $this->sWpVersion;
	}

	/**
	 * @param string $sVersionToMeet
	 *
	 * @return bool
	 */
	public function getWordpressIsAtLeastVersion( $sVersionToMeet ) {
		return version_compare( $this->getWordpressVersion(), $sVersionToMeet, '>=' );
	}

	/**
	 * @param array $aQueryParams
	 */
	public function redirectToAdmin( $aQueryParams = [] ) {
		$this->doRedirect( is_multisite() ? get_admin_url() : admin_url(), $aQueryParams );
	}

	/**
	 * @param string $sUrl
	 * @param array  $aQueryParams
	 * @param bool   $bSafe
	 * @param bool   $bProtectAgainstInfiniteLoops - if false, ignores the redirect loop protection
	 */
	public function doRedirect( $sUrl, $aQueryParams = [], $bSafe = true, $bProtectAgainstInfiniteLoops = true ) {
		$sUrl = empty( $aQueryParams ) ? $sUrl : add_query_arg( $aQueryParams, $sUrl );

		$oDp = $this->loadDP();
		// we prevent any repetitive redirect loops
		if ( $bProtectAgainstInfiniteLoops ) {
			if ( $oDp->FetchCookie( 'icwp-isredirect' ) == 'yes' ) {
				return;
			}
			else {
				$oDp->setCookie( 'icwp-isredirect', 'yes', 7 );
			}
		}

		// based on: https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage/
		// we now escape the URL to be absolutely sure since we can't guarantee the URL coming through there
		$sUrl = esc_url_raw( $sUrl );
		$bSafe ? wp_safe_redirect( $sUrl ) : wp_redirect( $sUrl );
		exit();
	}

	/**
	 * @return string
	 */
	public function getCurrentPage() {
		global $pagenow;
		return $pagenow;
	}

	/**
	 * @return string
	 */
	public function getUrl_CurrentAdminPage() {

		$sPage = $this->getCurrentPage();
		$sUrl = self_admin_url( $sPage );

		//special case for plugin admin pages.
		if ( $sPage == 'admin.php' ) {
			$sSubPage = $this->loadDP()->FetchGet( 'page' );
			if ( !empty( $sSubPage ) ) {
				$aQueryArgs = [
					'page' => $sSubPage,
				];
				$sUrl = add_query_arg( $aQueryArgs, $sUrl );
			}
		}
		return $sUrl;
	}

	public function getIsMobile() :bool {
		return function_exists( 'wp_is_mobile' ) && wp_is_mobile();
	}

	public function isMultisite() :bool {
		return \function_exists( 'is_multisite' ) && is_multisite();
	}

	/**
	 * @param string $sAbsWpConfigPath
	 * @param string $sAbsSitePath
	 */
	public function isWpConfigRelocated( $sAbsWpConfigPath, $sAbsSitePath ) :bool {
		return !\str_contains( \rtrim( $sAbsWpConfigPath, DIRECTORY_SEPARATOR ), \rtrim( $sAbsSitePath, DIRECTORY_SEPARATOR ) );
	}

	/**
	 * @param string $sKey
	 * @param        $sValue
	 * @return bool
	 */
	public function updateOption( $sKey, $sValue ) {
		return $this->isMultisite() ? update_site_option( $sKey, $sValue ) : update_option( $sKey, $sValue );
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getOption( $sKey, $mDefault = false ) {
		return $this->isMultisite() ? get_site_option( $sKey, $mDefault ) : get_option( $sKey, $mDefault );
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function deleteOption( $key ) {
		return $this->isMultisite() ? delete_site_option( $key ) : delete_option( $key );
	}

	/**
	 * @return string
	 */
	public function getCurrentWpAdminPage() {
		$DP = $this->loadDP();
		$script = $DP->FetchServer( 'SCRIPT_NAME' );
		if ( empty( $script ) ) {
			$script = $DP->FetchServer( 'PHP_SELF' );
		}
		if ( is_admin() && !empty( $script ) && \basename( $script ) == 'admin.php' ) {
			$current = $DP->FetchGet( 'page' );
		}
		return empty( $current ) ? '' : $current;
	}

	/**
	 * @param int|null $nTime
	 * @param bool     $bShowTime
	 * @param bool     $bShowDate
	 * @return string
	 */
	public function getTimeStringForDisplay( $nTime = null, $bShowTime = true, $bShowDate = true ) {
		$nTime = empty( $nTime ) ? $this->loadDP()->time() : $nTime;

		$sFullTimeString = $bShowTime ? $this->getTimeFormat() : '';
		if ( empty( $sFullTimeString ) ) {
			$sFullTimeString = $bShowDate ? $this->getDateFormat() : '';
		}
		else {
			$sFullTimeString = $bShowDate ? ( $sFullTimeString.' '.$this->getDateFormat() ) : $sFullTimeString;
		}
		return date_i18n( $sFullTimeString, $this->getTimeAsGmtOffset( $nTime ) );
	}

	/**
	 * @param int $nTime
	 * @return int
	 */
	public function getTimeAsGmtOffset( $nTime = null ) {

		$nTimezoneOffset = wp_timezone_override_offset();
		if ( $nTimezoneOffset === false ) {
			$nTimezoneOffset = $this->getOption( 'gmt_offset' );
			if ( empty( $nTimezoneOffset ) ) {
				$nTimezoneOffset = 0;
			}
		}

		$nTime = empty( $nTime ) ? $this->loadDP()->time() : $nTime;
		return $nTime + ( $nTimezoneOffset*HOUR_IN_SECONDS );
	}

	/**
	 * @return string
	 */
	public function getTimeFormat() {
		$sFormat = $this->getOption( 'time_format' );
		if ( empty( $sFormat ) ) {
			$sFormat = 'H:i';
		}
		return $sFormat;
	}

	/**
	 * @return string
	 */
	public function getDateFormat() {
		$sFormat = $this->getOption( 'date_format' );
		if ( empty( $sFormat ) ) {
			$sFormat = 'F j, Y';
		}
		return $sFormat;
	}

	/**
	 * @param string $version
	 * @return false|object
	 */
	public function getCoreUpdateByVersion( $version ) {
		$this->updatesCheck( 'core', true );
		require_once( ABSPATH.'wp-admin/includes/update.php' );
		$upgrade = find_core_update( $version, $this->getLocale() );
		return ( $upgrade === false ) ? find_core_update( $version, 'en_US' ) : $upgrade;
	}

	/**
	 * @param string $version
	 */
	public function getIfCoreUpdateExists( $version ) :bool {
		$upgrade = $this->getCoreUpdateByVersion( $version );
		return \is_object( $upgrade ) && $upgrade->current == $version;
	}

	/**
	 * @param string $context
	 * @param bool   $force
	 * @return array|stdClass|false
	 */
	public function updatesGather( $context = 'plugins', $force = false ) {
		if ( !in_array( $context, [ 'plugins', 'themes', 'core' ] ) ) {
			$context = 'plugins';
		}

		if ( $force ) {
			$this->updatesCheck( $context, $force );
		}
		require_once( ABSPATH.'wp-admin/includes/update.php' );
		return ( $context == 'core' ) ? get_core_updates() : $this->getTransient( 'update_'.$context );
	}

	public function doWpUpgrade() {
		require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
		wp_upgrade();
	}

	/**
	 * @param string $sContext - plugins, themes, core
	 * @param bool   $bForceRecheck
	 * @return void
	 */
	public function updatesCheck( $sContext, $bForceRecheck = false ) {
		global $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;

		if ( !in_array( $sContext, [ 'plugins', 'themes', 'core' ] ) ) {
			$sContext = 'plugins';
		}

		if ( $bForceRecheck ) {
			$sKey = sprintf( 'update_%s', $sContext );
			$response = $this->getTransient( $sKey );
			if ( !is_object( $response ) ) {
				$response = new \stdClass();
			}
			$response->last_checked = 0;
			$this->setTransient( $sKey, $response );
		}

		if ( $sContext == 'plugins' ) {
			wp_update_plugins();
		}
		elseif ( $sContext == 'themes' ) {
			wp_update_themes();
		}
		elseif ( $sContext == 'core' ) {
			wp_version_check();
		}
	}
}