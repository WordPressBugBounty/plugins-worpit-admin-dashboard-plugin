<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Core;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Reinstall extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public function process() :ApiResponse {
		$this->loadWpUpgrades();
		$WP = $this->loadWP();

		$oWpCoreUpdate = find_core_update( $WP->getWordpressVersion(), $WP->getLocale() );
		if ( empty( $oWpCoreUpdate ) ) {
			return $this->fail( 'Could not find Core Update object/data' );
		}

		$oWpCoreUpdate->response = 'reinstall';
		$result = ( new \Core_Upgrader( new \Automatic_Upgrader_Skin() ) )->upgrade( $oWpCoreUpdate );
		if ( is_wp_error( $result ) ) {
			return $this->fail( 'Re-install failed with error: '.$result->get_error_message() );
		}

		return $this->success( [ 'result' => $result ] );
	}
}