<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\Plugins;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class Update extends Base {

	use LegacyApi\Internal\Common\AutoOrLegacyUpdater;
	use LegacyApi\Internal\Common\Rollback;

	public function process() :LegacyApi\ApiResponse {
		$success = false;

		$file = $this->getFile();
		$data = [
			'rollback' => false,
		];

		$plugin = Plugins::Instance()->getPlugin( $file );
		if ( !empty( $plugin ) ) {
			$data[ 'rollback' ] = $this->getActionParam( 'do_rollback_prep' ) && $this->prepRollbackData( $file );

			$wasActive = Plugins::Instance()->isActive( $file );
			$wasNetworkActive = $this->loadWP()->isMultisite() && is_plugin_active_for_network( $file );
			$preVersion = $plugin[ 'Version' ];

			$this->isMethodAuto() ? $this->processAuto( $file ) : $this->processLegacy( $file );

			$plugin = Plugins::Instance()->getPlugin( $file );
			$success = !empty( $plugin ) && $preVersion !== $plugin[ 'Version' ];

			if ( $success && $wasActive && !Plugins::Instance()->isActive( $file ) ) {
				activate_plugin( $file, '', $wasNetworkActive );
			}
		}

		return $success ? $this->success( $data ) : $this->fail( 'Update failed', -1, $data );
	}

	/**
	 * @param string $mAsset
	 */
	protected function processAuto( $mAsset ) {
		( new LegacyApi\Internal\Common\RunAutoupdates() )->plugin( $mAsset );
	}

	/**
	 * @param string $mAsset
	 * @return mixed[]
	 */
	protected function processLegacy( $mAsset ) {
		$availableUpdates = $this->loadWP()->updatesGather( 'plugins' );
		if ( empty( $availableUpdates ) || empty( $availableUpdates->response[ $mAsset ] ) ) {
			$this->loadWP()->updatesCheck( 'plugins', true );
		}
		return $this->loadWpPlugins()->update( $mAsset );
	}
}