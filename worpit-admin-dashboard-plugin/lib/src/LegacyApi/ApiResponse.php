<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\StdClassAdapter;

/**
 * @property int    $authenticated
 * @property string $channel
 * @property int    $code
 * @property array  $data
 * @property bool   $die
 * @property string $handshake
 * @property string $error_message
 * @property string $message
 * @property int    $openssl_verify
 * @property bool   $success
 * @property string $status
 */
class ApiResponse {

	use StdClassAdapter;

	public function __construct() {
		$this->applyFromArray( [
			'error_message'  => '',
			'message'        => '',
			'success'        => true,
			'authenticated'  => 0,
			'channel'        => '',
			'die'            => false,
			'handshake'      => 'none',
			'openssl_verify' => -999,
			'data'           => [],
		] );
	}

	/**
	 * @param string $sMsg
	 * @return $this
	 * @deprecated 5.6
	 */
	public function setMessage( $sMsg ) {
		$this->message = $sMsg;
		return $this;
	}

	/**
	 * @param bool $bSuccess
	 * @return $this
	 * @deprecated 5.6
	 */
	public function setSuccess( $bSuccess = true ) {
		$this->success = (bool)$bSuccess;
		return $this;
	}

	/**
	 * @param string $sStatus
	 * @return $this
	 * @deprecated 5.6
	 */
	public function setStatus( $sStatus ) {
		$this->status = $sStatus;
		return $this;
	}

	/**
	 * @return \stdClass
	 */
	public function getResponsePackage() {
		return (object)$this->getRawDataAsArray();
	}
}