<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class FileSystem {

	protected static FileSystem $instance;

	/**
	 * @var \WP_Filesystem_Base
	 */
	protected $wpfs = null;

	public static function Instance() :self {
		return self::$instance ??= new self();
	}

	/**
	 * @return false|string
	 */
	public function getContents( string $pathname ) {
		return $this->fs() ? $this->fs()->get_contents( $pathname ) : \file_get_contents( $pathname );
	}

	public function delete( string $path ) :bool {
		return $this->fs() && $this->fs()->delete( $path, true );
	}

	public function isDir( string $pathname ) :bool {
		return $this->fs() && $this->fs()->is_dir( $pathname );
	}

	public function isDirEmpty( string $dir ) :bool {
		return \is_readable( $dir ) && \count( \scandir( $dir ) ) == 2;
	}

	public function isFile( string $pathname ) :bool {
		return $this->fs() && $this->fs()->is_file( $pathname );
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

	public function putFileContents( string $path, string $contents ) :bool {
		return ( $this->fs() && $this->fs()->put_contents( $path, $contents, FS_CHMOD_FILE ) )
			   || ( \function_exists( '\file_put_contents' ) && \file_put_contents( $path, $contents ) !== false );
	}

	/**
	 * @return string[]
	 */
	public function enumItemsInDir( string $dir ) :array {
		$files = [];
		try {
			if ( \is_dir( $dir ) ) {
				foreach ( new \FilesystemIterator( $dir ) as $file ) {
					/** @var \FilesystemIterator $file */
					$files[] = $file->getPathname();
				}
			}
		}
		catch ( \Exception $e ) {
		}
		return $files;
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

	public function createDummyDataFileRandomBytes( string $path, int $size = 1048576 /** Bytes */, $maxSegment = 1048576 ) :bool {
		$success = false;
		$dir = \dirname( $path );
		if ( \file_exists( $path ) ) {
			return false;
		}
		if ( $size > 1 && $maxSegment > 1 && $this->mkdir( $dir ) && ( $this->isDir( $dir ) || \is_dir( $dir ) ) ) {
			$maxSegment = \min( $maxSegment, $size );
			$createdFile = false;
			$h = false;
			try {
				$h = @\fopen( $path, 'xb' );
				if ( \is_resource( $h ) ) {
					$createdFile = true;
					$remaining = $size;
					do {
						$length = \min( $maxSegment, $remaining );
						$written = \fwrite( $h, \random_bytes( $length ) );
						if ( $written !== $length ) {
							throw new \RuntimeException( 'Failed to write dummy data segment.' );
						}
						$remaining -= $written;
					} while ( $remaining > 0 );
					$success = \fclose( $h ) && $remaining === 0;
					$h = false;
				}
			}
			catch ( \Exception|\Error $e ) {
			}
			finally {
				if ( \is_resource( $h ) ) {
					@\fclose( $h );
				}
				if ( !$success && $createdFile && \is_file( $path ) ) {
					$this->delete( $path ) || @\unlink( $path );
				}
			}
		}
		return $success;
	}

	/**
	 * @deprecated
	 */
	public function deleteDir( string $dir ) :bool {
		return $this->fs() && $this->fs()->delete( $dir, true );
	}
}
