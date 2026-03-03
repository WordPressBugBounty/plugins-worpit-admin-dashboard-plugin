<?php

class ICWP_APP_DataProcessor {

	/**
	 * @var \ICWP_APP_DataProcessor
	 */
	protected static $I = null;

	/**
	 * @var string
	 */
	protected static $sIpAddress = false;

	/**
	 * @var string
	 */
	protected static $nIpAddressVersion = false;

	/**
	 * @var int
	 */
	protected static $nRequestTime;

	protected function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	public static function GetRequestTime() :int {
		return self::$nRequestTime ?? self::$nRequestTime = \time();
	}

	/**
	 * @param bool $bAsHuman
	 * @return int|string|bool - visitor IP Address as IP2Long
	 */
	public function getVisitorIpAddress( $bAsHuman = true ) {

		if ( empty( self::$sIpAddress ) ) {
			self::$sIpAddress = $this->findViableVisitorIp();
		}

		if ( !self::$sIpAddress || $bAsHuman ) {
			return self::$sIpAddress;
		}

		// If it's IPv6 we never return as long (we can't!)
		return ( $this->getVisitorIpVersion() == 4 ) ? ip2long( self::$sIpAddress ) : self::$sIpAddress;
	}

	/**
	 * @param mixed $mItem
	 * @return false|string
	 */
	public function encodeJson( $mItem ) {
		return \function_exists( 'wp_json_encode' ) ? wp_json_encode( $mItem ) : \json_encode( $mItem );
	}

	/**
	 * Cloudflare compatible.
	 * @return string|bool
	 */
	protected function findViableVisitorIp() {

		$sourceOptions = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_REAL_IP',
			'HTTP_X_SUCURI_CLIENTIP',
			'HTTP_INCAP_CLIENT_IP',
			'HTTP_FORWARDED',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR'
		];

		$theIP = false;
		foreach ( $sourceOptions as $source ) {
			$ipToTest = self::FetchServer( $source );
			if ( !empty( $ipToTest ) ) {
				// comma-separated list is sometimes returned
				foreach ( \array_filter( \array_map( '\trim', \explode( ',', $ipToTest ) ) ) as $maybeIP ) {
					// this version checking serves to weed out IPv6 if filter_var isn't supported by their PHP.
					// I.e. We ONLY support IPv6 if filter_var() is supported.
					if ( $this->getIpAddressVersion( $maybeIP ) ) {
						$theIP = $maybeIP;
						break( 2 );
					}
				}
			}
		}
		return $theIP;
	}

	public function getRequestUri() :string {
		return (string)$this->FetchServer( 'REQUEST_URI' );
	}

	/**
	 * @param string $path
	 * @param string $ext
	 */
	public function addExtensionToFilePath( $path, $ext ) :string {
		if ( !\str_contains( $ext, '.' ) ) {
			$ext = '.'.$ext;
		}
		if ( !\str_ends_with( $path, $ext ) ) {
			$path = $path.$ext;
		}
		return $path;
	}

	/**
	 * @param string $sPath
	 * @return string
	 */
	public function getExtension( $sPath ) {
		$nLastPeriod = \strrpos( $sPath, '.' );
		return ( $nLastPeriod === false ) ? $sPath : str_replace( '.', '', substr( $sPath, $nLastPeriod ) );
	}

	/**
	 * @return bool|int|string
	 */
	public function getVisitorIpVersion() {
		if ( empty( self::$nIpAddressVersion ) ) {
			self::$nIpAddressVersion = $this->getIpAddressVersion( $this->getVisitorIpAddress( true ) );
		}
		return self::$nIpAddressVersion;
	}

	/**
	 * @param string $sEmail
	 */
	public function validEmail( $sEmail ) :bool {
		return !empty( $sEmail ) && is_email( $sEmail );
	}

	public function isUrlRewritten() :bool {
		return $this->FetchServer( 'SCRIPT_NAME', '' ) != ( \explode( "?", $this->getRequestUri() )[ 0 ] ?? '' );
	}

	/**
	 * @return bool
	 */
	public function isWindows() {
		return ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' );
	}

	/**
	 * Strength can be 1, 3, 7, 15
	 * @param int  $nLength
	 * @param int  $strength
	 * @param bool $bIgnoreAmb
	 * @return string
	 */
	public static function GenerateRandomString( $nLength = 10, $strength = 7, $bIgnoreAmb = true ) {
		$chars = [ 'abcdefghijkmnopqrstuvwxyz' ];

		if ( $strength & 2 ) {
			$chars[] = '023456789';
		}

		if ( $strength & 4 ) {
			$chars[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		}

		if ( $strength & 8 ) {
			$chars[] = '$%^&*#';
		}

		if ( !$bIgnoreAmb ) {
			$chars[] = 'OOlI1';
		}

		$password = '';
		$charset = \implode( '', $chars );
		for ( $i = 0 ; $i < $nLength ; $i++ ) {
			$password .= $charset[ ( wp_rand()%\strlen( $charset ) ) ];
		}
		return $password;
	}

	/**
	 * @param array  $aArray
	 * @param string $sKey The array key to fetch
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function ArrayFetch( $aArray, $sKey, $mDefault = null ) {
		return $aArray[ $sKey ] ?? $mDefault;
	}

	/**
	 * @param string $key The $_COOKIE key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchCookie( $key, $default = null ) {
		return self::ArrayFetch( $_COOKIE, $key, $default );
	}

	/**
	 * @param string $sKey
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchEnv( $sKey, $default = null ) {
		return self::ArrayFetch( $_ENV, $sKey, $default );
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchGet( $key, $default = null ) {
		return self::ArrayFetch( $_GET, $key, $default );
	}

	/**
	 * @param string $key The $_POST key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchPost( $key, $default = null ) {
		return self::ArrayFetch( $_POST, $key, $default );
	}

	/**
	 * @param string $key
	 * @param bool   $includeCookie
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchRequest( $key, $includeCookie = false, $default = null ) {
		$mFetchVal = self::FetchPost( $key );
		if ( \is_null( $mFetchVal ) ) {
			$mFetchVal = self::FetchGet( $key );
			if ( \is_null( $mFetchVal && $includeCookie ) ) {
				$mFetchVal = self::FetchCookie( $key );
			}
		}
		return \is_null( $mFetchVal ) ? $default : $mFetchVal;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchServer( $key, $default = null ) {
		return self::ArrayFetch( $_SERVER, $key, $default );
	}

	/**
	 * Use this to reliably read the contents of a PHP file that doesn't have executable
	 * PHP Code.
	 * Why use this? In the name of naive security, silly web hosts can prevent reading the contents of
	 * non-PHP files so we simply put the content we want to have read into a php file and then "include" it.
	 * @param string $file
	 * @return string
	 */
	public function readFileContentsUsingInclude( $file ) {
		\ob_start();
		include( $file );
		return \ob_get_clean();
	}

	/**
	 * @param      $sKey
	 * @param      $mValue
	 * @param int  $nExpireLength
	 * @param null $sPath
	 * @param null $sDomain
	 * @param bool $bSsl
	 * @return bool
	 */
	public function setCookie( $sKey, $mValue, $nExpireLength = 3600, $sPath = null, $sDomain = null, $bSsl = false ) {
		if ( \function_exists( 'headers_sent' ) && \headers_sent() ) {
			return false;
		}
		$_COOKIE[ $sKey ] = $mValue;
		return \setcookie(
			$sKey,
			$mValue,
			(int)( $this->time() + $nExpireLength ),
			( is_null( $sPath ) && defined( 'COOKIEPATH' ) ) ? COOKIEPATH : $sPath,
			( is_null( $sDomain ) && defined( 'COOKIE_DOMAIN' ) ) ? COOKIE_DOMAIN : $sDomain,
			$bSsl
		);
	}

	/**
	 * @param string $sKey
	 * @return bool
	 */
	public function setDeleteCookie( $sKey ) {
		unset( $_COOKIE[ $sKey ] );
		return $this->setCookie( $sKey, '', -3600 );
	}

	/**
	 * Effectively validates and IP Address.
	 * @param string $sIpAddress
	 * @return int|false
	 */
	public function getIpAddressVersion( $sIpAddress ) {
		if ( filter_var( $sIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return 4;
		}
		if ( filter_var( $sIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return 6;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getPhpVersion() {
		return \defined( 'PHP_VERSION' ) ? \PHP_VERSION : \phpversion();
	}

	/**
	 * @param string $atLeast
	 * @return bool
	 */
	public function getPhpVersionIsAtLeast( $atLeast ) {
		return \version_compare( $this->getPhpVersion(), $atLeast, '>=' );
	}

	/**
	 * @param array $a
	 * @return stdClass
	 */
	public function convertArrayToStdClass( $a ) {
		$o = new \stdClass();
		if ( !empty( $a ) && is_array( $a ) ) {
			foreach ( $a as $sKey => $mValue ) {
				$o->{$sKey} = $mValue;
			}
		}
		return $o;
	}

	/**
	 * @return int
	 */
	public function time() {
		return self::GetRequestTime();
	}
}