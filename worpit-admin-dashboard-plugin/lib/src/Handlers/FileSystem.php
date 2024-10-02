<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class FileSystem {

	/**
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * @var \WP_Filesystem_Base
	 */
	protected $wpfs = null;

	public static function Instance() :self {
		return self::$instance ?? self::$instance = new self();
	}

	public function deleteDir( string $dir ) :bool {
		return ( $this->fs() && $this->fs()->delete( $dir, true ) ) || @\rmdir( $dir );
	}

	public function isDir( string $path ) :bool {
		return ( $this->fs() && $this->fs()->is_dir( $path ) ) || @\is_dir( $path );
	}

	public function mkdir( string $path ) :bool {
		return wp_mkdir_p( $path );
	}

	/**
	 * @return \WP_Filesystem_Base|mixed|false
	 */
	protected function fs() {
		if ( \is_null( $this->wpfs ) ) {
			$this->wpfs = false;
			require_once( ABSPATH.'wp-admin/includes/file.php' );
			if ( \WP_Filesystem() ) {
				global $wp_filesystem;
				if ( isset( $wp_filesystem ) && \is_object( $wp_filesystem ) ) {
					$this->wpfs = $wp_filesystem;
				}
			}
		}
		return $this->wpfs;
	}
}