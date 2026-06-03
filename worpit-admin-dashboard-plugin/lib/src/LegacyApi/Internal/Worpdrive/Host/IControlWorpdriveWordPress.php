<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Host;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\{
	Plugins,
	Request
};
use FernleafSystems\WorpdriveClient\Host\WorpdriveWordPress;

class IControlWorpdriveWordPress implements WorpdriveWordPress {

	public function plugins() :array {
		$plugins = Plugins::Instance()->getPlugins();
		$enum = [];
		foreach ( $plugins as $file => $plugin ) {
			if ( \is_string( $file ) ) {
				$enum[ $file ] = [
					'name'    => $plugin[ 'Name' ] ?? '',
					'version' => $plugin[ 'Version' ] ?? '',
					'dir'     => \dirname( $file ),
					'active'  => (int)is_plugin_active( $file ),
				];
			}
		}
		\ksort( $enum );
		return \array_values( $enum );
	}

	public function themes() :array {
		$themes = \ICWP_APP_WpFunctions_Themes::GetInstance()->getThemes();
		$current = \ICWP_APP_WpFunctions_Themes::GetInstance()->getCurrent();
		$active = $current instanceof \WP_Theme ? $current->get_stylesheet() : '';
		$enum = [];
		foreach ( $themes as $theme ) {
			if ( $theme instanceof \WP_Theme ) {
				$enum[ $theme->get_stylesheet() ] = [
					'name'    => $theme->get( 'Name' ),
					'dir'     => $theme->get_stylesheet(),
					'version' => $theme->get( 'Version' ),
					'active'  => $active === $theme->get_stylesheet() ? 1 : 0,
				];
			}
		}
		\ksort( $enum );
		return \array_values( $enum );
	}

	public function homeUrl() :string {
		return \ICWP_APP_WpFunctions::GetInstance()->getHomeUrl();
	}

	public function wpUrl() :string {
		return \ICWP_APP_WpFunctions::GetInstance()->getSiteUrl();
	}

	public function restUrl() :string {
		return \function_exists( 'rest_url' ) ? (string)rest_url() : '';
	}

	public function contentUrl() :string {
		return content_url();
	}

	public function locale() :string {
		return get_locale();
	}

	public function timezoneString() :string {
		return \function_exists( 'wp_timezone_string' ) ? (string)wp_timezone_string() : '';
	}

	public function isMultisite() :bool {
		return is_multisite();
	}

	public function version() :string {
		return \function_exists( 'wp_get_wp_version' ) ? wp_get_wp_version() : \get_bloginfo( 'version' );
	}

	public function scriptFilename() :string {
		return (string)Request::Instance()->server( 'SCRIPT_FILENAME' );
	}
}
