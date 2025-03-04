<?php

class ICWP_APP_Foundation {

	use \FernleafSystems\Wordpress\Plugin\iControlWP\Control\PluginControllerConsumer;

	/**
	 * @var ICWP_APP_Render
	 */
	private static $oRender;

	/**
	 * @var ICWP_APP_WpComments
	 */
	private static $oWpComments;

	/**
	 * @return \ICWP_APP_DataProcessor
	 */
	public static function loadDP() {
		return \ICWP_APP_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	public static function loadFS() {
		return ICWP_APP_WpFilesystem::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	public static function loadWP() {
		return \ICWP_APP_WpFunctions::GetInstance();
	}

	public function loadWpPlugins() :\ICWP_APP_WpFunctions_Plugins {
		return \ICWP_APP_WpFunctions_Plugins::GetInstance();
	}

	public function loadWpFunctionsThemes() :\ICWP_APP_WpFunctions_Themes {
		return \ICWP_APP_WpFunctions_Themes::GetInstance();
	}

	/**
	 * @return ICWP_APP_Encrypt
	 */
	public function loadEncryptProcessor() {
		return ICWP_APP_Encrypt::GetInstance();
	}

	/**
	 * @return \ICWP_APP_WpDb
	 */
	public static function loadDbProcessor() {
		return \ICWP_APP_WpDb::GetInstance();
	}

	/**
	 * @param string $sTemplatePath
	 * @return ICWP_APP_Render
	 */
	public static function loadRenderer( $sTemplatePath = '' ) {
		if ( !isset( self::$oRender ) ) {
			self::$oRender = ICWP_APP_Render::GetInstance()
											->setAutoloaderPath( dirname( __FILE__ ).'/Twig/Autoloader.php' );
		}
		if ( !empty( $sTemplatePath ) ) {
			self::$oRender->setTemplateRoot( $sTemplatePath );
		}

		return self::$oRender;
	}

	/**
	 * @return ICWP_APP_WpAdminNotices
	 */
	public static function loadAdminNoticesProcessor() {
		return ICWP_APP_WpAdminNotices::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpUsers
	 */
	public static function loadWpUsers() {
		return ICWP_APP_WpUsers::GetInstance();
	}

	public static function loadWpUpgrades() {
		@include_once( ABSPATH.'wp-admin/includes/class-wp-upgrader.php' );
	}

	/**
	 * @return ICWP_APP_WpComments
	 */
	public static function loadWpCommentsProcessor() {
		if ( !isset( self::$oWpComments ) ) {
			self::$oWpComments = ICWP_APP_WpComments::GetInstance();
		}
		return self::$oWpComments;
	}
}