<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Plugins extends Base {

	public function process() :ApiResponse {
		return $this->success( [ 'wordpress-plugins' => $this->collect() ] );
	}

	public function collect() :array {

		$plugins = $this->getInstalledPlugins( $this->getDesiredPluginAttributes() );

		// option to do another update check? force it?
		$wpUpdates = $this->loadWP()->updatesGather( 'plugins', $this->isForceUpdateCheck() );

		$autoUpdates = $this->getAutoUpdates( 'plugins' );
		$sServicePluginBaseFile = \ICWP_Plugin::getController()->getPluginBaseFile();

		foreach ( $plugins as $file => &$data ) {
			$data[ 'active' ] = is_plugin_active( $file );
			$data[ 'auto_update' ] = (int)\in_array( $file, $autoUpdates );
			$data[ 'file' ] = $file;
			$data[ 'is_service_plugin' ] = ( $file == $sServicePluginBaseFile );
			$data[ 'network_active' ] = is_plugin_active_for_network( $file );
			$data[ 'update_available' ] = isset( $wpUpdates->response[ $file ] ) ? 1 : 0;
			$data[ 'update_info' ] = '';

			if ( $data[ 'update_available' ] ) {
				$updateInfo = $wpUpdates->response[ $file ];
				if ( isset( $updateInfo->sections ) ) {
					unset( $updateInfo->sections );
				}
				if ( isset( $updateInfo->changelog ) ) {
					unset( $updateInfo->changelog );
				}

				$data[ 'update_info' ] = json_encode( $updateInfo );
				if ( !empty( $updateInfo->slug ) ) {
					$data[ 'slug' ] = $updateInfo->slug;
				}
			}

			// $oCurrentUpdates->no_update seems to be relatively new
			if ( empty( $data[ 'slug' ] ) && !empty( $wpUpdates->no_update[ $file ]->slug ) ) {
				$data[ 'slug' ] = $wpUpdates->no_update[ $file ]->slug;
			}
		}
		return $plugins;
	}

	/**
	 * Gets all the installed plugin and filters out unnecessary information based on "desired attributes"
	 */
	protected function getInstalledPlugins( array $attributes = [] ) :array {
		$plugins = $this->loadWpPlugins()->getPlugins();
		if ( !empty( $attributes ) ) {
			foreach ( $plugins as $file => $aData ) {
				$plugins[ $file ] = \array_intersect_key( $aData, \array_flip( $attributes ) );
			}
		}
		return $plugins;
	}

	protected function getDesiredPluginAttributes() :array {
		return [
			'Name',
			'PluginURI',
			'Version',
			'Network',
			'slug',
			'Version'
		];
	}
}