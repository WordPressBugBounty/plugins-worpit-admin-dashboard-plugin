<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class Plugins {

	/**
	 * @var self
	 */
	protected static $instance = null;

	private function __construct() {
	}

	public static function Instance() :Plugins {
		return self::$instance ?? self::$instance = new self();
	}

	/**
	 * @return null|\WP_Error
	 */
	public function activate( string $file, bool $networkWide = false ) {
		return activate_plugin( $file, '', $networkWide );
	}

	public function deactivate( string $file, bool $networkWide = false ) {
		deactivate_plugins( $file, '', $networkWide );
	}

	public function delete( string $file, bool $networkWide = false ) :bool {
		if ( empty( $file ) || !$this->isInstalled( $file ) ) {
			return false;
		}

		if ( $this->isActive( $file ) ) {
			$this->deactivate( $file, $networkWide );
		}
		$this->uninstall( $file );

		// delete the folder
		$pluginDir = \dirname( $file );
		if ( $pluginDir == '.' ) { //it's not within a sub-folder
			$pluginDir = $file;
		}
		return FileSystem::Instance()->deleteDir( path_join( WP_PLUGIN_DIR, $pluginDir ) );
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

		$upgrader = new \Plugin_Upgrader( $skin = new \Automatic_Upgrader_Skin() );
		add_filter( 'upgrader_package_options', function ( $options ) use ( $overwrite ) {
			$options[ 'clear_destination' ] = $overwrite;
			return $options;
		} );

		$mResult = $upgrader->install( $sourceURL );

		return [
			'successful'  => $mResult === true,
			'feedback'    => $skin->get_upgrade_messages(),
			'plugin_info' => $upgrader->plugin_info(),
			'errors'      => is_wp_error( $mResult ) ? $mResult->get_error_messages() : [ 'no errors' ]
		];
	}

	public function uninstall( string $file ) {
		uninstall_plugin( $file );
	}

	/**
	 * @return bool|null
	 */
	protected function checkForUpdates() {
		if ( class_exists( '\WPRC_Installer' ) && \method_exists( '\WPRC_Installer', 'wprc_update_plugins' ) ) {
			\WPRC_Installer::wprc_update_plugins();
			return true;
		}
		elseif ( function_exists( 'wp_update_plugins' ) ) {
			return ( wp_update_plugins() !== false );
		}
		return null;
	}

	public function isActive( string $file ) :bool {
		return $this->isInstalled( $file ) && is_plugin_active( $file );
	}

	public function isInstalled( string $file ) :bool {
		return \array_key_exists( $file, $this->getPlugins() );
	}

	/**
	 * @return array|null
	 */
	public function getPlugin( string $file ) {
		return $this->getPlugins()[ $file ] ?? null;
	}

	/**
	 * @return array[]
	 */
	public function getPlugins() :array {
		if ( !\function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH.'wp-admin/includes/plugin.php' );
		}
		return \function_exists( 'get_plugins' ) ? get_plugins() : [];
	}
}