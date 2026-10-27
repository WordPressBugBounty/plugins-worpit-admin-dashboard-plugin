<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\Plugins;

class ICWP_APP_FeatureHandler_Autoupdates extends ICWP_APP_FeatureHandler_BaseApp {

	/**
	 * @param string $context
	 * @return array
	 */
	public function getAutoUpdates( $context = 'plugins' ) {
		$WP = $this->loadWP();

		$items = $this->getOpt( 'auto_update_'.$context, [] );
		if ( !is_array( $items ) ) {
			$items = [];
		}

		if ( $context == 'plugins' ) {
			$items = \array_intersect( \array_keys( Plugins::Instance()->getPlugins() ), $items );
		}

		// handover storage to WordPress itself
		$wpItems = $this->loadWP()->getOption( 'auto_update_'.$context, [] );
		if ( !\is_array( $wpItems ) ) {
			$wpItems = [];
		}
		$aAutoUpdateItems = \array_unique( \array_merge( $items, $wpItems ) );

		if ( \count( $aAutoUpdateItems ) != \count( $items )
			 || \count( \array_diff( $aAutoUpdateItems, $items ) ) > 0
			 || \count( \array_diff( $items, $aAutoUpdateItems ) ) > 0 ) {
			$WP->updateOption( 'auto_update_'.$context, $aAutoUpdateItems );
		}
		$this->setOpt( 'auto_update_'.$context, [] );

		return $aAutoUpdateItems;
	}

	/**
	 * @param array  $items
	 * @param string $context
	 * @return $this
	 */
	private function storeAutoUpdates( $items, $context = 'plugins' ) {
		if ( is_array( $items ) ) {
			$this->loadWP()->updateOption( 'auto_update_'.$context, array_unique( $items ) );
		}
		return $this;
	}

	/**
	 * @param string $sSlug
	 * @param bool   $bSetOn
	 * @param string $sContext
	 */
	public function setAutoUpdate( $sSlug, $bSetOn = false, $sContext = 'plugins' ) {
		$items = $this->getAutoUpdates( $sContext );

		if ( $bSetOn ) {
			$items[] = $sSlug;
		}
		else {
			$items = array_diff( $items, [ $sSlug ] );
		}

		$this->storeAutoUpdates( $items, $sContext );
	}
}