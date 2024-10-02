<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Theme;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\{
	ApiResponse,
	Internal
};

class Install extends Base {

	public function process() :ApiResponse {
		$params = $this->getActionParams();

		if ( empty( $params[ 'url' ] ) ) {
			return $this->fail( 'The URL was empty.' );
		}

		$params = \array_merge( [
			'activate'     => false,
			'overwrite'    => true,
			'network_wide' => false
		], $params );

		$installURL = wp_http_validate_url( $params[ 'url' ] );
		if ( !$installURL ) {
			return $this->fail( 'The URL did not pass the WordPress HTTP URL Validation.' );
		}

		try {
			$result = Handlers\Themes::Instance()->install( $installURL, (bool)$params[ 'overwrite' ] );
		}
		catch ( \Exception $e ) {
			$result = [
				'successful'  => false,
				'feedback'    => 'Exception: '.$e->getMessage(),
				'plugin_info' => '',
				'errors'      => [ $e->getMessage() ]
			];
		}

		if ( empty( $result[ 'successful' ] ) ) {
			return $this->fail( implode( ' | ', $result[ 'errors' ] ), -1, $result );
		}

		$theme = $result[ 'theme_info' ];
		if ( is_string( $theme ) ) {
			$theme = wp_get_theme( $theme );
		}

		if ( !\is_object( $theme ) || !$theme->exists() ) {
			return $this->fail( 'After installation, cannot load the theme.' );
		}

		if ( !empty( $params[ 'activate' ] ) && $theme->get_stylesheet_directory() !== get_stylesheet_directory() ) {
			Handlers\Themes::Instance()->activate( $theme->get_stylesheet() );
		}

		return $this->success( [
			'result'           => $result,
			'wordpress-themes' => ( new Internal\Collect\Themes() )
				->setRequestParams( $this->getRequestParams() )
				->collect(),
		] );
	}
}