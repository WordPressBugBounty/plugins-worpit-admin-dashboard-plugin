<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class Themes {

	/**
	 * @var self
	 */
	protected static $instance = null;

	private function __construct() {
	}

	public static function Instance() :Themes {
		return self::$instance ?? self::$instance = new self();
	}

	public function activate( string $stylesheet ) :bool {
		$success = false;
		if ( !empty( $stylesheet ) ) {
			$theme = $this->getTheme( $stylesheet );
			if ( $theme->exists() ) {
				switch_theme( $stylesheet );
				$success = get_stylesheet() === $stylesheet;
			}
		}
		return $success;
	}

	/**
	 * @return bool|\WP_Error|null
	 */
	public function delete( string $stylesheet ) {
		require_once( ABSPATH.'wp-admin/includes/theme.php' );
		return $this->exists( $stylesheet ) && \function_exists( 'delete_theme' ) ? delete_theme( $stylesheet ) : false;
	}

	public function exists( string $stylesheet ) :bool {
		$theme = $this->getTheme( $stylesheet );
		return $theme instanceof \WP_Theme && $theme->exists();
	}

	/**
	 * @return ?\WP_Theme
	 */
	public function getCurrent() {
		return $this->getTheme( get_stylesheet() );
	}

	/**
	 * @return null|\WP_Theme
	 */
	public function getTheme( string $stylesheet ) {
		if ( !\function_exists( 'wp_get_theme' ) ) {
			require_once( ABSPATH.'wp-admin/includes/theme.php' );
		}
		return \function_exists( 'wp_get_theme' ) ? wp_get_theme( $stylesheet ) : null;
	}

	/**
	 * @throws \Exception
	 */
	public function install( string $sourceURL, bool $overwrite = true ) :array {
		@include_once( ABSPATH.'wp-admin/includes/class-wp-upgrader.php' );

		if ( !\class_exists( '\Automatic_Upgrader_Skin' ) ) {
			throw new \Exception( sprintf( "Class (%s) doesn't exist as we're expecting.", 'Automatic_Upgrader_Skin' ) );
		}
		if ( !\class_exists( '\Plugin_Upgrader' ) ) {
			throw new \Exception( sprintf( "Class (%s) doesn't exist as we're expecting.", 'Plugin_Upgrader' ) );
		}

		$upgrader = new \Theme_Upgrader( $skin = new \Automatic_Upgrader_Skin() );
		add_filter( 'upgrader_package_options', function ( $options ) use ( $overwrite ) {
			$options[ 'clear_destination' ] = $overwrite;
			return $options;
		} );

		$mResult = $upgrader->install( $sourceURL );

		return [
			'successful' => $mResult === true,
			'feedback'   => $skin->get_upgrade_messages(),
			'theme_info' => $upgrader->theme_info(),
			'errors'     => is_wp_error( $mResult ) ? $mResult->get_error_messages() : [ 'no errors' ]
		];
	}
}