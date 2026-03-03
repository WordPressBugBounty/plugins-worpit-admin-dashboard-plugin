<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\Request;
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\StdClassAdapter;

/**
 * @property string $accname
 * @property string $key
 * @property string $pin
 * @property int    $api_hook
 * @property int    $api_priority
 * @property string $action
 * @property array  $action_params - serialized string
 * @property string $a             - 'check' for link
 * @property string $m             - channel
 * @property array  $ftpcred
 * @property bool   $icwpenc
 * @property bool   $worpit_api    - deprecated
 * @property bool   $worpit_link
 * @property string $package_name
 * @property string $hmac_hash
 * @property string $hmac_algo
 * @property string $verification_code
 * @property string $opensig
 * @property int    $verify_ts
 * @property int    $timeout
 * @property bool   $icwpapi
 * @property int    $silent_login
 * @property int    $wpadmin_user
 * @property string $token         - login token
 * @property string $nonce
 * @property string $zip_id
 */
class RequestParameters {

	use StdClassAdapter {
		__get as __adapterGet;
	}

	/**
	 * @param string $theGET
	 * @param string $thePOST
	 */
	public function __construct( $theGET, $thePOST ) {
		if ( !\is_array( $theGET ) ) {
			$theGET = empty( $theGET ) ? [] : @\json_decode( @\base64_decode( $theGET ), true );
		}
		if ( !\is_array( $thePOST ) ) {
			$thePOST = empty( $thePOST ) ? [] : @\json_decode( @\base64_decode( $thePOST ), true );
		}
		$this->applyFromArray( \array_merge(
			\is_array( $_GET ) ? $_GET : [],
			\is_array( $_POST ) ? $_POST : [],
			\is_array( $theGET ) ? $theGET : [],
			\is_array( $thePOST ) ? $thePOST : []
		) );
	}

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {

		$value = $this->__adapterGet( $sProperty );

		switch ( $sProperty ) {
			case 'action_params':
				if ( !\is_array( $value ) ) {
					$value = $this->parseActionParams();
				}
				break;
			case 'm':
				$value = empty( $value ) ? 'index' : $value;
				break;
			case 'accname':
				$value = \urldecode( $value );
				break;
			case 'opensig':
				$value = \base64_decode( $value );
				break;
			case 'timeout':
				if ( \is_null( $value ) ) {
					$value = 60;
				}
				$value = (int)$value;
				break;
			case 'verification_code':
				if ( \is_null( $value ) ) {
					$value = 'no code';
				}
				break;
			case 'verify_ts':
				$value = (int)$value;
				break;
			default:
				break;
		}

		return $value;
	}

	protected function parseActionParams() :array {
		$actionParams = [];
		foreach ( \is_array( \getallheaders() ) ? \getallheaders() : [] as $key => $value ) {
			if ( \trim( \strtolower( $key ) ) === 'content-type' && \strtolower( $value ) === 'application/json' ) {
				$input = Request::Instance()->input();
				if ( !empty( $input ) ) {
					$actionParams = @\json_decode( $input, true )[ 'action_params' ] ?? [];
				}
				break;
			}
		}
		return \is_array( $actionParams ) ? $actionParams : [];
	}

	/**
	 * @return string
	 */
	public function getApiHook() {
		if ( empty( $this->api_hook ) || !is_string( $this->api_hook ) ) {
			$this->api_hook = is_admin() ? 'admin_init' : 'wp_loaded';
			if ( class_exists( 'WooDojo_Maintenance_Mode', false ) || class_exists( 'ITSEC_Core', false ) ) {
				$this->api_hook = 'init';
			}
		}
		return $this->api_hook;
	}

	/**
	 * @return string
	 */
	public function getPin() {
		return $this->pin;
	}

	/**
	 * @return int
	 */
	public function getTimeout() {
		return (int)$this->timeout;
	}

	/**
	 * @return int
	 */
	public function getApiHookPriority() {
		$pri = $this->api_priority;
		if ( !\is_numeric( $pri ) ) {
			$pri = is_admin() ? 101 : 1;
			if ( \class_exists( 'ITSEC_Core', false ) ) {
				$pri = 100;
			}
		}
		return (int)$pri;
	}

	/**
	 * @param string $sKey
	 * @param string $mDefault
	 * @return string
	 */
	public function getStringParam( $sKey, $mDefault = '' ) {
		$sVal = $this->getParam( $sKey, $mDefault );
		return ( !empty( $sVal ) && is_string( $sVal ) ) ? trim( $sVal ) : $mDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getParam( $sKey, $mDefault = '' ) {
		return $this->{$sKey};
	}

	/**
	 * @return bool
	 * @deprecated 4.5
	 */
	public function isSilentLogin() {
		return (bool)$this->silent_login;
	}
}