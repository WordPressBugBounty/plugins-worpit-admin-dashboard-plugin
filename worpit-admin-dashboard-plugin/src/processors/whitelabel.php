<?php

class ICWP_APP_Processor_Whitelabel extends ICWP_APP_Processor_BaseApp {

	public function run() {
		add_filter( self::con()->doPluginPrefix( 'plugin_labels' ), [ $this, 'doRelabelPlugin' ] );
		add_filter( 'plugin_row_meta', [ $this, 'fRemoveDetailsMetaLink' ], 200, 2 );
		add_filter( self::con()->doPluginPrefix( 'main_extracontent' ), [ $this, 'addExtraContent' ] );
	}

	/**
	 * @param string $sExtraContent
	 * @return string
	 */
	public function addExtraContent( $sExtraContent = '' ) {
		return apply_filters( 'icwp-whitelabel-extracontent', '' );
	}

	/**
	 * @param array $aPluginLabels
	 * @return array
	 */
	public function doRelabelPlugin( $aPluginLabels ) {
		// these are the old white labelling keys which will be replaced upon final release of white labelling.
		$sServiceName = $this->mod->getOpt( 'service_name' );
		if ( !empty( $sServiceName ) ) {
			$aPluginLabels[ 'Name' ] = $sServiceName;
			$aPluginLabels[ 'Title' ] = $sServiceName;
			$aPluginLabels[ 'Author' ] = $sServiceName;
			$aPluginLabels[ 'AuthorName' ] = $sServiceName;
		}
		$sTagLine = $this->mod->getOpt( 'tag_line' );
		if ( !empty( $sTagLine ) ) {
			$aPluginLabels[ 'Description' ] = $sTagLine;
		}
		$sUrl = $this->mod->getOpt( 'plugin_home_url' );
		if ( !empty( $sUrl ) ) {
			$aPluginLabels[ 'PluginURI' ] = $sUrl;
			$aPluginLabels[ 'AuthorURI' ] = $sUrl;
		}

		$sIcon16 = $this->mod->getOpt( 'icon_url_16x16' );
		if ( !empty( $sIcon16 ) ) {
			$aPluginLabels[ 'icon_url_16x16' ] = $sIcon16;
		}

		$sIcon32 = $this->mod->getOpt( 'icon_url_32x32' );
		if ( !empty( $sIcon32 ) ) {
			$aPluginLabels[ 'icon_url_32x32' ] = $sIcon32;
		}

		return $aPluginLabels;
	}

	/**
	 * @filter
	 * @param array  $meta
	 * @param string $basefile
	 * @return array
	 */
	public function fRemoveDetailsMetaLink( $meta, $basefile ) {
		if ( $basefile == self::con()->getPluginBaseFile() ) {
			if ( isset( $meta[ 2 ] ) && \strpos( $meta[ 2 ], 'plugin=worpit-admin-dashboard-plugin' ) > 0 ) {
				unset( $meta[ 2 ] );
			}
		}
		return $meta;
	}
}