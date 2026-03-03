<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class ICWP_APP_WpFilesystem {

	/**
	 * @var \ICWP_APP_WpFilesystem
	 */
	protected static $I = null;

	/**
	 * @var \WP_Filesystem_Base
	 */
	protected $wpfs = null;

	/**
	 * @var string
	 */
	protected $sWpConfigPath = null;

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	/**
	 * @param $sFilePath
	 * @return bool|null    true/false whether file/directory exists
	 */
	public function exists( $sFilePath ) {
		$oFs = $this->getWpfs();
		if ( $oFs && $oFs->exists( $sFilePath ) ) {
			return true;
		}
		return function_exists( 'file_exists' ) ? file_exists( $sFilePath ) : null;
	}

	/**
	 * @return string
	 */
	public function getWpConfigPath() {
		return $this->sWpConfigPath;
	}

	/**
	 * @param string $sUrl
	 * @param array  $aRequestArgs
	 *
	 * @return array|bool
	 */
	public function requestUrl( $sUrl, $aRequestArgs = [] ) {
		$mResult = wp_remote_request( $sUrl, $aRequestArgs );
		if ( is_wp_error( $mResult ) ) {
			return false;
		}
		if ( !isset( $mResult[ 'response' ][ 'code' ] ) || $mResult[ 'response' ][ 'code' ] != 200 ) {
			return false;
		}
		return $mResult;
	}

	/**
	 * @param string $sUrl
	 * @param array  $args
	 *
	 * @return bool
	 */
	public function getUrl( $sUrl, $args = [] ) {
		$args[ 'method' ] = 'GET';
		return $this->requestUrl( $sUrl, $args );
	}

	/**
	 * @param string $sUrl
	 * @param array  $aRequestArgs
	 *
	 * @return false|string
	 */
	public function getUrlContent( $sUrl, $aRequestArgs = [] ) {
		$response = $this->getUrl( $sUrl, $aRequestArgs );
		if ( !$response || !isset( $response[ 'body' ] ) ) {
			return false;
		}
		return $response[ 'body' ];
	}

	/**
	 * @param string $url
	 * @param array  $args
	 * @return bool
	 */
	public function postUrl( $url, $args = [] ) {
		$args[ 'method' ] = 'POST';
		return $this->requestUrl( $url, $args );
	}

	/**
	 * @param string $sFilePath
	 * @return int|null
	 */
	public function getModifiedTime( $sFilePath ) {
		return $this->getTime( $sFilePath, 'modified' );
	}

	/**
	 * @param string $sFilePath
	 * @param string $property
	 * @return int|null
	 */
	public function getTime( $sFilePath, $property = 'modified' ) {
		if ( !$this->exists( $sFilePath ) ) {
			return null;
		}
		$fs = $this->getWpfs();
		switch ( $property ) {
			case 'modified' :
				return $fs ? $fs->mtime( $sFilePath ) : filemtime( $sFilePath );
			case 'accessed' :
				return $fs ? $fs->atime( $sFilePath ) : fileatime( $sFilePath );
			default:
				return null;
		}
	}

	/**
	 * @param string $sFilePath
	 * @return string|null
	 */
	public function getFileContent( $sFilePath ) {
		$contents = null;
		$fs = $this->getWpfs();
		if ( $fs ) {
			$contents = $fs->get_contents( $sFilePath );
		}

		if ( empty( $contents ) && \function_exists( 'file_get_contents' ) ) {
			$contents = \file_get_contents( $sFilePath );
		}
		return $contents;
	}

	/**
	 * @param $sFilePath
	 * @return bool
	 */
	public function getFileSize( $sFilePath ) {
		$fs = $this->getWpfs();
		if ( $fs && ( $fs->size( $sFilePath ) > 0 ) ) {
			return $fs->size( $sFilePath );
		}
		return @\filesize( $sFilePath );
	}

	/**
	 * @param string $path
	 * @param string $contents
	 * @return bool
	 */
	public function putFileContent( $path, $contents ) {
		$fs = $this->getWpfs();
		if ( $fs && $fs->put_contents( $path, $contents, FS_CHMOD_FILE ) ) {
			return true;
		}

		if ( \function_exists( 'file_put_contents' ) ) {
			return \file_put_contents( $path, $contents ) !== false;
		}
		return false;
	}

	/**
	 * Recursive delete
	 *
	 * @param string $dir
	 * @return bool
	 */
	public function deleteDir( $dir ) {
		return FileSystem::Instance()->deleteDir( $dir );
	}

	/**
	 * @param string $path
	 * @return bool|null
	 */
	public function deleteFile( $path ) {
		$fs = $this->getWpfs();
		if ( $fs && $fs->delete( $path ) ) {
			return true;
		}
		return \function_exists( '\unlink' ) ? @\unlink( $path ) : null;
	}

	/**
	 * @param string $source
	 * @param string $target
	 * @return bool|null
	 */
	public function move( $source, $target ) {
		return FileSystem::Instance()->move( (string)$source, (string)$target );
	}

	/**
	 * @param string $path
	 * @return bool|null
	 */
	public function isDir( $path ) {
		$fs = $this->getWpfs();
		if ( $fs && $fs->is_dir( $path ) ) {
			return true;
		}
		return \function_exists( 'is_dir' ) ? @\is_dir( $path ) : null;
	}

	/**
	 * @param $sFilePath
	 * @return bool|mixed
	 */
	public function isFile( $sFilePath ) {
		$fs = $this->getWpfs();
		if ( $fs && $fs->is_file( $sFilePath ) ) {
			return true;
		}
		return \function_exists( 'is_file' ) ? @\is_file( $sFilePath ) : null;
	}

	/**
	 * @param $path
	 */
	public function mkdir( $path ) :bool {
		return wp_mkdir_p( $path );
	}

	/**
	 * @param string $path
	 * @param int    $time
	 * @return bool
	 */
	public function touch( $path, $time = null ) :bool {
		return FileSystem::Instance()->touch( (string)$path, $time === null ? \time() : (int)$time );
	}

	/**
	 * @return \WP_Filesystem_Base
	 */
	public function getWpfs() {
		if ( is_null( $this->wpfs ) ) {
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