<?php
if ( !class_exists( 'ICWP_APP_WpFilesystem', false ) ):

	class ICWP_APP_WpFilesystem {

		/**
		 * @var ICWP_APP_WpFilesystem
		 */
		protected static $oInstance = NULL;

		/**
		 * @var WP_Filesystem_Base
		 */
		protected $oWpfs = null;

		/**
		 * @var string
		 */
		protected $sWpConfigPath = null;

		/**
		 * @return ICWP_APP_WpFilesystem
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		/**
		 * @param string $sBase
		 * @param string $sPath
		 * @return string
		 */
		public function pathJoin( $sBase, $sPath ) {
			return rtrim( $sBase, DIRECTORY_SEPARATOR ).DIRECTORY_SEPARATOR.ltrim( $sPath, DIRECTORY_SEPARATOR );
		}

		/**
		 * @param $sFilePath
		 * @return boolean|null	true/false whether file/directory exists
		 */
		public function exists( $sFilePath ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->exists( $sFilePath ) ) {
				return true;
			}
			return function_exists( 'file_exists' ) ? file_exists( $sFilePath ) : null;
		}

		/**
		 * @param string $sNeedle
		 * @param string $sDir
		 * @param boolean $bIncludeExtension
		 * @param boolean $bCaseSensitive
		 *
		 * @return bool|null
		 */
		public function fileExistsInDir( $sNeedle, $sDir, $bIncludeExtension = true, $bCaseSensitive = false ) {
			if ( empty( $sNeedle ) || empty( $sDir ) ) {
				return false;
			}

			if ( !$bCaseSensitive ) {
				$sNeedle = strtolower( $sNeedle );
			}

			$oDirIt = null;
			$bUseDirectoryIterator = class_exists( 'DirectoryIterator', false );
			if ( $bUseDirectoryIterator ) {
				try {
					$oDirIt = new DirectoryIterator( $sDir );
				}
				catch( Exception $oE ) { //  UnexpectedValueException, RuntimeException, Exception
					$bUseDirectoryIterator = false; // Path doesn't exist or don't have access to open
				}
			}

			if ( $bUseDirectoryIterator && $oDirIt ) {

				//if the file you're searching for doesn't have an extension, then we don't include extensions in search
				$nDotPosition = strpos( $sNeedle, '.' );
				$bHasExtension = $nDotPosition !== false;
				$bIncludeExtension = $bIncludeExtension && $bHasExtension;

				$sNeedlePreExtension = $bHasExtension ? substr( $sNeedle, 0, $nDotPosition ) : $sNeedle;

				$bFound = false;
				foreach ( $oDirIt as $oFileItem ) {
					if ( !$oFileItem->isFile() ) {
						continue;
					}
					$sFilename = $oFileItem->getFilename();
					if ( !$bCaseSensitive ) {
						$sFilename = strtolower( $sFilename );
					}

					if ( $bIncludeExtension ) {
						$bFound = ( $sFilename == $sNeedle );
					}
					else {
						// This is not entirely accurate as it only finds whether a file "starts" with needle, ignoring subsequent characters
						$bFound = ( strpos( $sFilename, $sNeedlePreExtension ) === 0 );
					}

					if ( $bFound ) {
						break;
					}
				}

				return $bFound;
			}

			if ( $bCaseSensitive ) {
				return $this->exists( $this->pathJoin( $sDir, $sNeedle ) );
			}
			$sNeedle = strtolower( $sNeedle );
			if ( $oHandle = opendir( $sDir ) ) {

				while ( false !== ( $sFileEntry = readdir( $oHandle ) ) ) {
					if ( !$this->isFile( $this->pathJoin( $sDir, $sFileEntry ) ) ) {
						continue;
					}
					if ( $sNeedle == strtolower( $sFileEntry ) ) {
						return true;
					}
				}
			}

			return false;
		}

		protected function setWpConfigPath() {
			$this->sWpConfigPath = ABSPATH.'wp-config.php';
			if ( !$this->exists($this->sWpConfigPath)  ) {
				$this->sWpConfigPath = ABSPATH.'../wp-config.php';
				if ( !$this->exists($this->sWpConfigPath)  ) {
					$this->sWpConfigPath = false;
				}
			}
		}

		public function getContent_WpConfig() {
			return $this->getFileContent( $this->sWpConfigPath );
		}

		/**
		 * @param string $sContent
		 * @return bool
		 */
		public function putContent_WpConfig( $sContent ) {
			return $this->putFileContent( $this->sWpConfigPath, $sContent );
		}

		/**
		 * @param string $sUrl
		 * @param boolean $bSecure
		 *
		 * @return boolean
		 */
		public function getIsUrlValid( $sUrl, $bSecure = false ) {
			$sSchema = $bSecure? 'https://' : 'http://';
			$sUrl = ( strpos( $sUrl, 'http' ) !== 0 )? $sSchema.$sUrl : $sUrl;
			return ( $this->getUrl( $sUrl ) != false );
		}

		/**
		 * @return string
		 */
		public function getWpConfigPath() {
			return $this->sWpConfigPath;
		}

		/**
		 * @param string $sUrl
		 * @param array $aRequestArgs
		 *
		 * @return array|bool
		 */
		public function requestUrl( $sUrl, $aRequestArgs = array() ) {

			$mResult = wp_remote_request( $sUrl, $aRequestArgs );
			if ( is_wp_error( $mResult ) ) {
				return false;
			}
			if ( !isset( $mResult['response']['code'] ) || $mResult['response']['code'] != 200 ) {
				return false;
			}
			return $mResult;
		}

		/**
		 * @param string $sUrl
		 * @param array $aRequestArgs
		 *
		 * @return bool
		 */
		public function getUrl( $sUrl, $aRequestArgs = array() ) {
			$aRequestArgs['method'] = 'GET';
			return $this->requestUrl( $sUrl, $aRequestArgs );
		}

		/**
		 * @param string $sUrl
		 * @param array $aRequestArgs
		 *
		 * @return false|string
		 */
		public function getUrlContent( $sUrl, $aRequestArgs = array() ) {
			$aResponse = $this->getUrl( $sUrl, $aRequestArgs );
			if ( !$aResponse || !isset( $aResponse['body'] ) ) {
				return false;
			}
			return $aResponse['body'];
		}

		/**
		 * @param string $sUrl
		 * @param array $aRequestArgs
		 *
		 * @return bool
		 */
		public function postUrl( $sUrl, $aRequestArgs = array() ) {
			$aRequestArgs['method'] = 'POST';
			return $this->requestUrl( $sUrl, $aRequestArgs );
		}

		public function getCanWpRemoteGet() {
			$aUrlsToTest = array(
				'https://www.microsoft.com',
				'https://www.google.com',
				'https://www.facebook.com'
			);
			foreach( $aUrlsToTest as $sUrl ) {
				if ( $this->getUrl( $sUrl ) !== false ) {
					return true;
				}
			}
			return false;
		}

		public function getCanDiskWrite() {
			$sFilePath = dirname( __FILE__ ).'/testfile.'.rand().'txt';
			$sContents = "Testing icwp file read and write.";

			// Write, read, verify, delete.
			if ( $this->putFileContent( $sFilePath, $sContents ) ) {
				$sFileContents = $this->getFileContent( $sFilePath );
				if ( !is_null( $sFileContents ) && $sFileContents === $sContents ) {
					return $this->deleteFile( $sFilePath );
				}
			}
			return false;
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
		 * @return int|null
		 */
		public function getAccessedTime( $sFilePath ) {
			return $this->getTime( $sFilePath, 'accessed' );
		}

		/**
		 * @param string $sFilePath
		 * @param string $sProperty
		 * @return int|null
		 */
		public function getTime( $sFilePath, $sProperty = 'modified' ) {

			if ( !$this->exists( $sFilePath ) ) {
				return null;
			}

			$oFs = $this->getWpfs();
			switch ( $sProperty ) {

				case 'modified' :
					return $oFs? $oFs->mtime( $sFilePath ) : filemtime( $sFilePath );
					break;
				case 'accessed' :
					return $oFs? $oFs->atime( $sFilePath ) : fileatime( $sFilePath );
					break;
				default:
					return null;
					break;
			}
		}

		/**
		 * @param string $sFilePath
		 * @return NULL|boolean
		 */
		public function getCanReadWriteFile( $sFilePath ) {
			if ( !file_exists( $sFilePath ) ) {
				return null;
			}

			$nFileSize = filesize( $sFilePath );
			if ( $nFileSize === 0 ) {
				return null;
			}

			$sFileContent = $this->getFileContent( $sFilePath );
			if ( empty( $sFileContent ) ) {
				return false; //can't even read the file!
			}
			return $this->putFileContent( $sFilePath, $sFileContent );
		}

		/**
		 * @param string $sFilePath
		 * @return string|null
		 */
		public function getFileContent( $sFilePath ) {
			$sContents = null;
			$oFs = $this->getWpfs();
			if ( $oFs ) {
				$sContents = $oFs->get_contents( $sFilePath );
			}

			if ( empty( $sContents ) && function_exists( 'file_get_contents' ) ) {
				$sContents = file_get_contents( $sFilePath );
			}
			return $sContents;
		}

		/**
		 * @param $sFilePath
		 * @return bool
		 */
		public function getFileSize( $sFilePath ) {
			$oFs = $this->getWpfs();
			if ( $oFs && ( $oFs->size( $sFilePath ) > 0 ) ) {
				return $oFs->size( $sFilePath );
			}
			return @filesize( $sFilePath );
		}

		/**
		 * @param string|null $sBaseDir
		 * @param string $sPrefix
		 * @param string $outsRandomDir
		 * @return bool|string
		 */
		public function getTempDir( $sBaseDir = null, $sPrefix = '', &$outsRandomDir = '' ) {
			$sTemp = rtrim( (is_null( $sBaseDir )? get_temp_dir(): $sBaseDir), DIRECTORY_SEPARATOR ).DIRECTORY_SEPARATOR;

			$sCharset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz0123456789';
			do {
				$sDir = $sPrefix;
				for ( $i = 0; $i < 8; $i++ ) {
					$sDir .= $sCharset[(rand() % strlen( $sCharset ))];
				}
			}
			while ( is_dir( $sTemp.$sDir ) );

			$outsRandomDir = $sDir;

			$bSuccess = true;
			if ( !@mkdir( $sTemp.$sDir, 0755, true ) ) {
				$bSuccess = false;
			}
			return ($bSuccess? $sTemp.$sDir: false);
		}

		/**
		 * @param string $sFilePath
		 * @param string $sContents
		 * @return boolean
		 */
		public function putFileContent( $sFilePath, $sContents ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->put_contents( $sFilePath, $sContents, FS_CHMOD_FILE ) ) {
				return true;
			}

			if ( function_exists( 'file_put_contents' ) ) {
				return file_put_contents( $sFilePath, $sContents ) !== false;
			}
			return false;
		}

		/**
		 * Recursive delete
		 *
		 * @param string $sDir
		 * @return bool
		 */
		public function deleteDir( $sDir ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->delete( $sDir, true ) ) {
				return true;
			}
			return @rmdir( $sDir );
		}

		/**
		 * @param string $sFilePath
		 * @return boolean|null
		 */
		public function deleteFile( $sFilePath ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->delete( $sFilePath ) ) {
				return true;
			}
			return function_exists( 'unlink' ) ? @unlink( $sFilePath ) : null;
		}

		/**
		 * @param string $sFilePathSource
		 * @param string $sFilePathDestination
		 * @return bool|null
		 */
		public function move( $sFilePathSource, $sFilePathDestination ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->move( $sFilePathSource, $sFilePathDestination ) ) {
				return true;
			}
			return function_exists( 'rename' ) ? @rename( $sFilePathSource, $sFilePathDestination ) : null;
		}

		/**
		 * @param string $sFilePath
		 * @return bool|null
		 */
		public function isDir( $sFilePath ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->is_dir( $sFilePath ) ) {
				return true;
			}
			return function_exists( 'is_dir' ) ? @is_dir( $sFilePath ) : null;
		}

		/**
		 * @param string $sDir
		 * @return bool
		 */
		public function isDirEmpty( $sDir ) {
			if ( !is_readable( $sDir) ) {
				return null;
			}
			return ( count( scandir( $sDir ) ) == 2 );
		}

		/**
		 * @param $sFilePath
		 * @return bool|mixed
		 */
		public function isFile( $sFilePath ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->is_file( $sFilePath ) ) {
				return true;
			}
			return function_exists( 'is_file' ) ? @is_file( $sFilePath ) : null;
		}

		/**
		 * @param $sDirPath
		 * @return bool
		 */
		public function mkdir( $sDirPath ) {
			return wp_mkdir_p( $sDirPath );
		}

		/**
		 * @param string $sFilePath
		 * @param int $nTime
		 * @return bool|mixed
		 */
		public function touch( $sFilePath, $nTime = null ) {
			$oFs = $this->getWpfs();
			if ( $oFs && $oFs->touch( $sFilePath, $nTime ) ) {
				return true;
			}
			return function_exists( 'touch' ) ? @touch( $sFilePath, $nTime ) : null;
		}

		/**
		 * @return WP_Filesystem_Base
		 */
		protected function getWpfs() {
			if ( is_null( $this->oWpfs ) ) {
				$this->initFileSystem();
			}
			return $this->oWpfs;
		}

		/**
		 */
		private function initFileSystem() {
			if ( is_null( $this->oWpfs ) ) {
				$this->oWpfs = false;
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				if ( WP_Filesystem() ) {
					global $wp_filesystem;
					if ( isset( $wp_filesystem ) && is_object( $wp_filesystem ) ) {
						$this->oWpfs = $wp_filesystem;
					}
				}
			}
		}
	}
endif;
