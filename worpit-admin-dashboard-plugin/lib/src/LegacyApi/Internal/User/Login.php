<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\User;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Login extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	const LoginTokenKey = 'icwplogintoken';

	public function process() :ApiResponse {
		$source = home_url().'$'.\uniqid().'$'.\time();
		$token = hash( 'sha256', $source );

		$this->loadWP()
			 ->setTransient(
				 self::LoginTokenKey,
				 [
					 'token'    => $token,
					 'redirect' => $this->getActionParam( 'redirect', '' )
				 ],
				 \MINUTE_IN_SECONDS
			 );

		return $this->success( [
			'source' => $source,
			'token'  => $token
		] );
	}
}