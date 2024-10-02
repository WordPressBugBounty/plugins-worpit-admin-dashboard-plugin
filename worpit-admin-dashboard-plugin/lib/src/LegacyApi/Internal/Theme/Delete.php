<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Theme;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect\Themes;

class Delete extends Base {

	public function process() :ApiResponse {
		$stylesheet = $this->getFile();
		if ( empty( $stylesheet ) ) {
			return $this->fail( 'Stylesheet provided was empty.' );
		}

		if ( !Handlers\Themes::Instance()->exists( $stylesheet ) ) {
			return $this->fail( sprintf( 'Theme does not exist with Stylesheet: %s', $stylesheet ) );
		}

		$toDelete = Handlers\Themes::Instance()->getTheme( $stylesheet );
		if ( $toDelete->get_stylesheet_directory() === get_stylesheet_directory() ) {
			return $this->fail( sprintf( 'Cannot uninstall the currently active WordPress theme: %s', $stylesheet ) );
		}

		$result = Handlers\Themes::Instance()->delete( $stylesheet );
		if ( is_wp_error( $result ) ) {
			return $this->fail( $result->get_error_message() );
		}

		return $this->success( [
			'result' => (int)$result,
			'wordpress-themes' => ( new Themes() )->setRequestParams( $this->getRequestParams() )->collect(),
		] );
	}
}