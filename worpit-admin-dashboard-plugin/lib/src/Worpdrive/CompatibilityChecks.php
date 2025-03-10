<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\{
	Plugins,
	Request
};
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Filesystem\CanWriteToDir;

class CompatibilityChecks extends BaseHandler {

	/**
	 * Many of these data point are stored in the archive meta, so changes here must consider how meta is gathered and
	 * stored in the WD archive meta "snapshot"
	 */
	public function run() :array {
		$con = self::con();
		$WP = \ICWP_APP_WpFunctions::GetInstance();
		return [
			'server'   => [
				'ip' => $this->ip(),
			],
			'wp'       => [
				'wp_version'   => \get_bloginfo( 'version' ),
				'url_site'     => $WP->getHomeUrl(),
				'url_wp'       => $WP->getSiteUrl(),
				'locale'       => get_locale(),
				'wplang'       => \defined( 'WPLANG' ) ? WPLANG : '',
				'is_multisite' => is_multisite(),
				'plugins'      => $this->plugins(),
				'themes'       => $this->themes(),
			],
			'versions' => [
				'php'  => \phpversion(),
				'icwp' => $con->getVersion(),
			],
			'paths'    => $this->paths(),
			'ini'      => $this->ini(),
			'caps'     => $this->caps(),
			'exts'     => \is_array( \get_loaded_extensions() ) ? \get_loaded_extensions() : [],
		];
	}

	private function plugins() :array {
		$plugins = Plugins::Instance()->getPlugins();
		$enum = [];
		foreach ( $plugins as $file => $p ) {
			$enum[ $file ] = [
				'name'    => $p[ 'Name' ] ?? '',
				'version' => $p[ 'Version' ] ?? '',
				'active'  => (int)is_plugin_active( $file ),
			];
		}
		\ksort( $enum );
		return \array_values( $enum );
	}

	private function themes() :array {
		$themes = \ICWP_APP_WpFunctions_Themes::GetInstance()->getThemes();
		$enum = [];
		$active = \ICWP_APP_WpFunctions_Themes::GetInstance()->getCurrent()->get_stylesheet();
		foreach ( $themes as $t ) {
			if ( $t instanceof \WP_Theme ) {
				$enum[ $t->get_stylesheet() ] = [
					'name'    => $t->get( 'Name' ),
					'version' => $t->get( 'Version' ),
					'active'  => $active === $t->get_stylesheet() ? 1 : 0,
				];
			}
		}
		\ksort( $enum );
		return \array_values( $enum );
	}

	private function ip() :string {
		$ip = '';
		$body = wp_remote_retrieve_body( wp_remote_get( 'https://ip-detect.workers.aptoweb.com' ) );
		if ( !empty( $body ) ) {
			$ip = \json_decode( $body, true )[ 'ip' ] ?? '';
		}
		return $ip;
	}

	private function caps() :array {
		try {
			( new CanWriteToDir() )->run( self::con()->getPath_Temp( 'test_write_dir' ) );
			$canWrite = true;
		}
		catch ( \Exception $e ) {
			$canWrite = false;
		}
		return [
			'can_memory_limit'  => \function_exists( 'wp_is_ini_value_changeable' ) ? (int)wp_is_ini_value_changeable( 'memory_limit' ) : -1,
			'can_write_dir_tmp' => (int)$canWrite,
			'can_zip_archive'   => \class_exists( '\ZipArchive' ),
			'can_app_passwords' => \function_exists( 'wp_is_application_passwords_supported' ) ? (int)wp_is_application_passwords_supported() : -1,
		];
	}

	private function ini() :array {
		return [
			'max_execution_time' => \ini_get( 'max_execution_time' ),
		];
	}

	private function paths() :array {
		$wpContent = \defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '';
		return [
			'wp_abspath'      => trailingslashit( ABSPATH ),
			'script_filename' => (string)Request::Instance()->server( 'SCRIPT_FILENAME' ),
			'dir_content'     => (string)$wpContent,
			'dir_plugins'     => \defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : null,
			'dir_includes'    => path_join( $wpContent, \defined( 'WPINC' ) ? WPINC : '' ),
			'WPINC'           => \defined( 'WPINC' ) ? WPINC : null,
			'icwp_plugin_dir' => self::con()->getRootDir(),
		];
	}
}