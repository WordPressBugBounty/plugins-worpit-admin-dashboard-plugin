<?php

class ICWP_APP_WpFunctions_Themes extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpFunctions_Themes
	 */
	protected static $I = null;

	private function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ?? self::$I = new self();
	}

	public function activate( string $stylesheet ) :bool {
		if ( empty( $stylesheet )
			 || empty( $this->getTheme( $stylesheet ) ) || !$this->getTheme( $stylesheet )->exists() ) {
			return false;
		}

		switch_theme( $this->getTheme( $stylesheet )->get_stylesheet() );

		// Now test currently active theme
		$current = $this->getCurrent();
		return !\is_null( $current ) && $stylesheet == $current->get_stylesheet();
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
		$mResult = ( new Theme_Upgrader( $oSkin ) )->bulk_upgrade( [ $sFile ] );

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
	 * @return null|WP_Theme
	 */
	public function getCurrent() {
		return $this->getTheme();
	}

	/**
	 * @param string $stylesheet
	 * @return null|WP_Theme
	 */
	public function getTheme( $stylesheet = null ) {
		if ( !\function_exists( 'wp_get_theme' ) ) {
			require_once( ABSPATH.'wp-admin/includes/theme.php' );
		}
		$theme = \function_exists( 'wp_get_theme' ) ? wp_get_theme( $stylesheet ) : null;
		return empty( $theme ) ? ( $this->getThemes()[ $stylesheet ] ?? null ) : $theme;
	}

	public function getThemes() :array {
		if ( !\function_exists( 'wp_get_themes' ) ) {
			require_once( ABSPATH.'wp-admin/includes/theme.php' );
		}
		return \function_exists( 'wp_get_themes' ) ? wp_get_themes() : [];
	}
}