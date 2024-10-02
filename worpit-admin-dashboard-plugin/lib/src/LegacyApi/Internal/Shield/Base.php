<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Shield;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\Shield\Controller\Controller|null
	 */
	protected function getShieldController() {
		$con = null;
		try {
			if ( \function_exists( 'shield_security_get_plugin' ) ) {
				$con = shield_security_get_plugin()->getController();
			}
			else {
				global $oICWP_Wpsf;
				if ( isset( $oICWP_Wpsf ) ) {
					$con = $oICWP_Wpsf->getController();
				}
			}
		}
		catch ( \Exception $e ) {
		}
		return $con;
	}

	protected function isInstalled() :bool {
		return !\is_null( $this->getShieldController() );
	}
}