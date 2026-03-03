<?php

class ICWP_APP_Processor_Autoupdates extends ICWP_APP_Processor_BaseApp {

	public function run() {
		$nFilterPriority = $this->getHookPriority();
		if ( !$this->loadWP()->getWordpressIsAtLeastVersion( '5.5' ) ) {
			add_filter( 'auto_update_plugin', [ $this, 'autoupdate_plugins' ], $nFilterPriority, 2 );
			add_filter( 'auto_update_theme', [ $this, 'autoupdate_themes' ], $nFilterPriority, 2 );
		}
	}

	/**
	 * @param bool             $doUpdate
	 * @param \stdClass|string $mItem
	 * @return bool
	 */
	public function autoupdate_plugins( $doUpdate, $mItem ) {

		if ( is_object( $mItem ) && isset( $mItem->plugin ) ) { // WP 3.8.2+
			$sItemFile = $mItem->plugin;
		}
		elseif ( is_string( $mItem ) ) { // WP pre-3.8.2
			$sItemFile = $mItem;
		}
		else { // we don't have a slug to use so we just return the current update setting
			return $doUpdate;
		}

		if ( \in_array( $sItemFile, $this->mod->getAutoUpdates() ) ) {
			$doUpdate = true;
		}

		return $doUpdate;
	}

	/**
	 * @param bool             $doUpdate
	 * @param \stdClass|string $mItem
	 * @return bool
	 */
	public function autoupdate_themes( $doUpdate, $mItem ) {
		if ( is_object( $mItem ) && isset( $mItem->theme ) ) { // WP 3.8.2+
			$sItemFile = $mItem->theme;
		}
		elseif ( is_string( $mItem ) ) { // WP pre-3.8.2
			$sItemFile = $mItem;
		}
		else {
			return $doUpdate;
		}

		if ( in_array( $sItemFile, $this->mod->getAutoUpdates( 'themes' ) ) ) {
			$doUpdate = true;
		}
		return $doUpdate;
	}

	protected function getHookPriority() :int {
		return (int)$this->mod->getOpt( 'action_hook_priority', 1001 );
	}
}