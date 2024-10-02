<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\ZipDownload;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\{
	Plugins,
	Themes
};
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\File\ZipDir;

class Create extends Base {

	/**
	 * @throws \Exception
	 */
	public function plugin( string $file ) :array {
		if ( !Plugins::Instance()->isInstalled( $file ) ) {
			throw new \Exception( sprintf( 'Plugin for file is not installed: %s', $file ) );
		}
		return $this->createFrom( \dirname( path_join( WP_PLUGIN_DIR, $file ) ) );
	}

	/**
	 * @throws \Exception
	 */
	public function theme( string $file ) :array {
		$theme = Themes::Instance()->getTheme( $file );
		if ( empty( $theme ) ) {
			throw new \Exception( sprintf( 'Theme for stylesheet is not installed: %s', $file ) );
		}
		return $this->createFrom( $theme->get_stylesheet_directory() );
	}

	/**
	 * @throws \Exception
	 */
	protected function createFrom( string $sourceDir ) :array {
		if ( !ZipDir::IsSupported() ) {
			throw new \Exception( 'ZipDir is not supported' );
		}

		$FS = $this->loadFS();

		if ( !$FS->isDir( $sourceDir ) ) {
			throw new \Exception( sprintf( 'Plugin directory does not exist: %s', $sourceDir ) );
		}

		$zipDir = $this->getZipsDir();
		$ID = sanitize_key( uniqid( basename( $sourceDir ).'-' ) );
		$zipFile = path_join( $zipDir, $ID.'.zip' );

		if ( !( new ZipDir() )->run( $sourceDir, $zipFile ) ) {
			throw new \Exception( sprintf( 'ZipDir execution failed: %s', $zipDir ) );
		}

		$size = $FS->getFileSize( $zipFile );
		if ( empty( $size ) ) {
			throw new \Exception( 'Zip file size is empty' );
		}

		return [
			'id'   => $ID,
			'file' => $zipFile,
			'size' => $size,
		];
	}
}
