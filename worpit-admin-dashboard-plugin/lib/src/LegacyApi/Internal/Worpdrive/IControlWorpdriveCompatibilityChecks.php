<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\WorpdriveClient\CompatibilityChecks;
use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class IControlWorpdriveCompatibilityChecks extends CompatibilityChecks {

	protected function wp() :array {
		$WP = WorpdriveRuntime::host()->wordpress();
		return [
			'wp_version'   => $WP->version(),
			'url_home'     => $WP->homeUrl(),
			'url_site'     => $WP->homeUrl(),
			'url_wp'       => $WP->wpUrl(),
			'url_content'  => $WP->contentUrl(),
			'locale'       => $WP->locale(),
			'wplang'       => \defined( 'WPLANG' ) ? WPLANG : '',
			'is_multisite' => $WP->isMultisite(),
			'plugins'      => $WP->plugins(),
			'themes'       => $WP->themes(),
		];
	}

	protected function db() :?array {
		return null;
	}

	protected function versions() :array {
		$host = WorpdriveRuntime::host();
		return [
			'php'    => \phpversion(),
			'driver' => $host->pluginVersion(),
			'wp'     => $host->wordpress()->version(),
			'icwp'   => $host->pluginVersion(),
		];
	}

	protected function paths() :array {
		$host = WorpdriveRuntime::host();
		$wpContent = \defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '';
		return [
			'wp_abspath'      => trailingslashit( ABSPATH ),
			'script_filename' => $host->wordpress()->scriptFilename(),
			'dir_content'     => (string)$wpContent,
			'dir_plugins'     => \defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : null,
			'dir_includes'    => path_join( $wpContent, \defined( 'WPINC' ) ? WPINC : '' ),
			'url_content'     => \defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : null,
			'WPINC'           => \defined( 'WPINC' ) ? WPINC : null,
			'icwp_plugin_dir' => $host->rootDir(),
		];
	}
}
