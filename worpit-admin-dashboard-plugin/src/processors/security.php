<?php

class ICWP_APP_Processor_Security extends ICWP_APP_Processor_BaseApp {

	public function run() {
		if ( $this->mod->getOptIs( 'disallow_file_edit', 'Y' ) ) {
			if ( !defined( 'DISALLOW_FILE_EDIT' ) ) {
				define( 'DISALLOW_FILE_EDIT', true );
			}
			add_filter( 'user_has_cap', [ $this, 'disallowFileEditing' ], 100, 3 );
		}

		if ( $this->mod->getOptIs( 'force_ssl_admin', 'Y' ) && function_exists( 'force_ssl_admin' ) ) {
			if ( !defined( 'FORCE_SSL_ADMIN' ) ) {
				define( 'FORCE_SSL_ADMIN', true );
			}
			force_ssl_admin( true );
		}

		if ( $this->mod->getOptIs( 'hide_wp_version', 'Y' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		if ( $this->mod->getOptIs( 'hide_wlmanifest_link', 'Y' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		if ( $this->mod->getOptIs( 'hide_rsd_link', 'Y' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
	}

	/**
	 * @param array $aAllCaps
	 * @param array $aCap
	 * @param array $aArgs
	 *
	 * @return array
	 */
	public function disallowFileEditing( $aAllCaps, $aCap, $aArgs ) {

		$aEditCapabilities = [ 'edit_themes', 'edit_plugins', 'edit_files' ];
		$sRequestedCapability = $aArgs[ 0 ];

		if ( !in_array( $sRequestedCapability, $aEditCapabilities ) ) {
			return $aAllCaps;
		}
		$aAllCaps[ $sRequestedCapability ] = false;
		return $aAllCaps;
	}
}