<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\User;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Enumerate extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public function process() :ApiResponse {
		if ( !\function_exists( 'get_users' ) ) {
			include( ABSPATH.'wp-includes/user.php' );
		}
		return $this->success( [
			'wpusers'  => $this->enumUsers(),
			'wp_roles' => $this->enumRoles(),
		] );
	}

	private function enumUsers() :array {
		return get_users( [
			'fields'   => [
				'ID',
				'user_login',
				'display_name',
				'user_email',
				'user_registered',
				'roles'
			],
			'role__in' => empty( $this->getActionParam( 'role' ) ) ? [] : $this->getActionParam( 'role' ),
		] );
	}

	private function enumRoles() :array {
		global $wp_roles;
		return \array_map(
			function ( $attr ) {
				return $attr[ 'name' ];
			},
			\is_object( $wp_roles ) ? $wp_roles->roles : []
		);
	}
}