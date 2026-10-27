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
		return [
			'wp_abspath'      => trailingslashit( ABSPATH ),
			'script_filename' => $host->wordpress()->scriptFilename(),
			'dir_content'     => (string)WP_CONTENT_DIR,
			'dir_plugins'     => WP_PLUGIN_DIR,
			'dir_includes'    => path_join( WP_CONTENT_DIR, WPINC ),
			'url_content'     => WP_CONTENT_URL,
			'WPINC'           => WPINC,
			'icwp_plugin_dir' => $host->rootDir(),
		];
	}
}
