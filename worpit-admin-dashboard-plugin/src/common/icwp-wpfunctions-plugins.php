<?php

class ICWP_APP_WpFunctions_Plugins extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpFunctions_Plugins
	 */
	protected static $I = null;

	private function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	/**
	 * @param string $sFile
	 * @return mixed[]
	 */
	public function update( $sFile ) {
		$this->loadWpUpgrades();

		$oSkin = $this->loadWP()->getWordpressIsAtLeastVersion( '3.7' ) ?
			new \Automatic_Upgrader_Skin()
			: new \ICWP_Upgrader_Skin_Legacy();
		$mResult = ( new Plugin_Upgrader( $oSkin ) )->bulk_upgrade( [ $sFile ] );

		$aErrors = [];
		/** @var array|\WP_Error $mDetails */
		$mDetails = ( is_array( $mResult ) && isset( $mResult[ $sFile ] ) ) ? $mResult[ $sFile ] : [];
		if ( empty( $mDetails ) ) {
			$aErrors[] = 'False - Filesystem Error';
		}
		elseif ( is_wp_error( $mDetails ) ) {
			$mDetails = [];
			$aErrors = $mDetails->get_error_messages();
		}

		return [
			'successful' => empty( $aErrors ),
			'errors'     => $aErrors,
			'details'    => $mDetails,
			'feedback'   => method_exists( $oSkin, 'get_upgrade_messages' ) ? $oSkin->get_upgrade_messages() : [],
		];
	}

	/**
	 * @return array[]
	 * @deprecated 4.5.0
	 */
	public function getPlugins() {
		if ( !function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH.'wp-admin/includes/plugin.php' );
		}
		return \function_exists( 'get_plugins' ) ? get_plugins() : [];
	}
}