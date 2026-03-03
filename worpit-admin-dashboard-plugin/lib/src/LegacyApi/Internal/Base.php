<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

abstract class Base extends \ICWP_APP_Foundation {

	/**
	 * @var ApiResponse
	 */
	protected $actionResponse;

	/**
	 * @var LegacyApi\RequestParameters
	 */
	protected $oRequestParams;

	public function preProcess() {
		if ( $this->isIgnoreUserAbort() ) {
			\ignore_user_abort( true );
		}
	}

	abstract public function process() :ApiResponse;

	public function getStandardResponse() :ApiResponse {
		return $this->actionResponse ?? $this->actionResponse = new ApiResponse();
	}

	/**
	 * @return $this
	 */
	public function setStandardResponse( ApiResponse $response ) {
		$this->actionResponse = $response;
		return $this;
	}

	/**
	 * @param string $msg
	 */
	protected function success( array $executionData = [], $msg = '' ) :ApiResponse {
		$r = $this->getStandardResponse();
		$r->success = true;
		$r->message = sprintf( 'INTERNAL Package Execution SUCCEEDED with message: "%s".', $msg );
		$r->data = empty( $executionData ) ? [ 'success' => 1 ] : $executionData;
		$r->code = 0;
		return $r;
	}

	/**
	 * @param string $errorMessage
	 * @param int    $nErrorCode
	 * @param mixed  $mErrorData
	 */
	protected function fail( $errorMessage = '', $nErrorCode = -1, $mErrorData = [] ) :ApiResponse {
		$r = $this->getStandardResponse();
		$r->success = false;
		$r->message = $r->error_message = $errorMessage;
		$r->data = $mErrorData;
		$r->code = $nErrorCode;
		return $r;
	}

	protected function getActionParams() :array {
		return $this->getRequestParams()->action_params;
	}

	/**
	 * @param mixed|null $mDefault
	 * @return mixed|null
	 */
	protected function getActionParam( string $key, $mDefault = null ) {
		return $this->getActionParams()[ $key ] ?? $mDefault;
	}

	/**
	 * @return LegacyApi\RequestParameters
	 */
	public function getRequestParams() {
		return $this->oRequestParams;
	}

	/**
	 * @param LegacyApi\RequestParameters $oRequestParams
	 * @return $this
	 */
	public function setRequestParams( $oRequestParams ) {
		$this->oRequestParams = $oRequestParams;
		return $this;
	}

	protected function isForceUpdateCheck() :bool {
		$params = $this->getActionParams();
		return !isset( $params[ 'force_update_check' ] ) || $params[ 'force_update_check' ];
	}

	protected function isIgnoreUserAbort() :bool {
		$params = $this->getActionParams();
		return isset( $params[ 'ignore_user_abort' ] ) && $params[ 'ignore_user_abort' ];
	}
}