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

		$oSkin = new \Automatic_Upgrader_Skin();
		$mResult = ( new Plugin_Upgrader( $oSkin ) )->bulk_upgrade( [ $sFile ] );

		$aErrors = [];
		/** @var array|\WP_Error $mDetails */
		$mDetails = ( is_array( $mResult ) && isset( $mResult[ $sFile ] ) ) ? $mResult[ $sFile ] : [];
		if ( empty( $mDetails ) ) {
			$aErrors[] = 'False - Filesystem Error';
		}
		elseif ( is_wp_error( $mDetails ) ) {
			$aErrors = $mDetails->get_error_messages();
			$mDetails = [];
		}

		return [
			'successful' => empty( $aErrors ),
			'errors'     => $aErrors,
			'details'    => $mDetails,
			'feedback'   => $oSkin->get_upgrade_messages(),
		];
	}
}