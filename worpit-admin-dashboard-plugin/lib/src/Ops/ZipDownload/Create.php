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
			throw new \Exception( sprintf( 'Plugin for file is not installed: %s', esc_html( $file ) ) );
		}
		return $this->createFrom( \dirname( path_join( WP_PLUGIN_DIR, $file ) ) );
	}

	/**
	 * @throws \Exception
	 */
	public function theme( string $file ) :array {
		$theme = Themes::Instance()->getTheme( $file );
		if ( empty( $theme ) ) {
			throw new \Exception( sprintf( 'Theme for stylesheet is not installed: %s', esc_html( $file ) ) );
		}
		return $this->createFrom( $theme->get_stylesheet_directory() );
	}

	/**
	 * @throws \Exception
	 */
	public function createFrom( string $source ) :array {
		if ( !ZipDir::IsSupported() ) {
			throw new \Exception( 'ZipDir is not supported' );
		}

		$FS = $this->loadFS();

		if ( !$FS->exists( $source ) ) {
			throw new \Exception( sprintf( 'File/Directory does not exist: %s', esc_html( $source ) ) );
		}

		$zipDir = $this->getZipsDir();
		$ID = \preg_replace( '#[^a-z0-9_\-]#i', '', \basename( $source ).'-'.\base64_encode( \random_bytes( 16 ) ) );
		$zipFile = path_join( $zipDir, $ID.'.zip' );

		if ( !( new ZipDir() )->run( $source, $zipFile ) ) {
			throw new \Exception( sprintf( 'ZipDir execution failed: %s', esc_html( $zipDir ) ) );
		}

		$size = $FS->getFileSize( $zipFile );
		if ( empty( $size ) ) {
			throw new \Exception( 'Zip file size is empty' );
		}

		return [
			'id'     => $ID,
			'file'   => $zipFile,
			'size'   => $size,
			'sha256' => \hash_file( 'sha256', $zipFile ),
		];
	}
}
