<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

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
			'activate'     => true,
			'overwrite'    => true,
			'network_wide' => false
		], $params );

		$installURL = wp_http_validate_url( $params[ 'url' ] );
		if ( !$installURL ) {
			return $this->fail( 'The URL did not pass the WordPress HTTP URL Validation.' );
		}

		try {
			$result = Handlers\Plugins::Instance()->install( $installURL, (bool)$params[ 'overwrite' ] );
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

		//activate as required
		$pluginFile = $result[ 'plugin_info' ];
		if ( !empty( $pluginFile ) && $params[ 'activate' ] ) {
			Handlers\Plugins::Instance()->activate( $pluginFile, (bool)$params[ 'network_wide' ] );
		}

		wp_cache_flush(); // since we've added a plugin

		return $this->success( [
			'result'            => $result,
			'wordpress-plugins' => ( new Internal\Collect\Plugins() )
				->setRequestParams( $this->getRequestParams() )
				->collect(),
		] );
	}
}