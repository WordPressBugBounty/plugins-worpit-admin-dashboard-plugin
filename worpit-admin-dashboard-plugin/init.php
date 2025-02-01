<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Control\{
	Controller,
	PluginControllerConsumer
};

/** @var string $sIcwpPluginRootFile */
require_once( \dirname( __FILE__ ).'/lib/vendor/autoload.php' );

class ICWP_Plugin {

	use PluginControllerConsumer;

	/**
	 * @var Controller
	 */
	protected static $con;

	private static $i;

	public static function GetInstance() :self {
		return self::$i;
	}

	public function __construct( Controller $con ) {
		self::$i = $this;
		self::$con = $con;
	}

	/**
	 * @return Controller
	 */
	public static function getController() {
		return self::$con;
	}

	public function boot() {
		$this->getController()->loadAllFeatures();
	}

	/**
	 * @return \ICWP_APP_FeatureHandler_AutoUpdates
	 */
	public static function GetAutoUpdatesSystem() {
		return self::getController()->loadFeatureHandler( [ 'slug' => 'autoupdates' ] );
	}
}

if ( !class_exists( 'Worpit_Plugin' ) ) {
	class Worpit_Plugin extends ICWP_Plugin {

	}
}

$oICWP_App_Controller = Controller::GetInstance( $sIcwpPluginRootFile );
global $g_oWorpit;
$g_oWorpit = new \ICWP_Plugin( $oICWP_App_Controller );
$g_oWorpit->boot();