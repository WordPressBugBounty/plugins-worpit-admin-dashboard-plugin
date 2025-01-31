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

	/**
	 * @return false|string
	 */
	public function getContents( string $pathname ) {
		return $this->fs() ? $this->fs()->get_contents( $pathname ) : \file_get_contents( $pathname );
	}

	public function deleteDir( string $dir ) :bool {
		return $this->fs() && $this->fs()->delete( $dir, true );
	}

	public function isDir( string $pathname ) :bool {
		return $this->fs() && $this->fs()->is_dir( $pathname );
	}

	public function isDirEmpty( string $dir ) :bool {
		return \is_readable( $dir ) && \count( \scandir( $dir ) ) == 2;
	}

	public function mkdir( string $pathname ) :bool {
		return wp_mkdir_p( $pathname );
	}

	public function move( string $source, string $target ) :bool {
		return $this->fs() && $this->fs()->move( $source, $target );
	}

	public function touch( string $pathname, int $ts = 0 ) :bool {
		return $this->fs() && $this->fs()->touch( $pathname, $ts );
	}

	/**
	 * @return \WP_Filesystem_Base|mixed|false
	 */
	public function fs() {
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