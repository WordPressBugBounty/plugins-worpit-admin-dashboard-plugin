<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Shield\Options;

class Update extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Shield\Base {

	public function process() :\FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse {
		if ( !$this->isInstalled() ) {
			return $this->success( [
				'version' => 'not-installed' // \iControlWP\Shield\ShieldPluginConnectionStatus::REMOTE_NOT_INSTALLED
			] );
		}

		$con = $this->getShieldController();

		if ( \version_compare( $con->cfg->version(), '20.0', '<' ) ) {
			return $this->fail( sprintf( 'Shield version not supported: %s', $con->cfg->version() ) );
		}

		foreach ( $this->getActionParam( 'shield_options' ) as $options ) {
			foreach ( $options as $key => $value ) {
				$con->opts->optSet( $key, $value );
			}
		}
		return $this->success();
	}
}