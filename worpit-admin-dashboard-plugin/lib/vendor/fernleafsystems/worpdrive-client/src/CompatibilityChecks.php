<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class CompatibilityChecks extends BaseHandler {

	private array $checkParams;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $checkParams, string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS );
		$this->checkParams = $checkParams;
	}

	/**
	 * Many of these data point are stored in the archive meta, so changes here must consider how meta is gathered and
	 * stored in the WD archive meta "snapshot"
	 */
	public function run() :array {
		$status = [
			'server'   => $this->server(),
			'wp'       => $this->wp(),
		];
		$db = $this->db();
		if ( $db !== null ) {
			$status[ 'db' ] = $db;
		}
		return \array_merge(
			$status,
			[
				'versions' => $this->versions(),
				'paths'    => $this->paths(),
				'ini'      => $this->ini(),
				'caps'     => $this->caps(),
				'exts'     => \get_loaded_extensions(),
			]
		);
	}

	protected function server() :array {
		return [
			'ip'              => $this->ip(),
			'disk_free_space' => \function_exists( '\disk_free_space' ) ? \disk_free_space( ABSPATH ) : -1,
		];
	}

	protected function wp() :array {
		$host = WorpdriveRuntime::host();
		$WP = $host->wordpress();
		return [
			'wp_version'   => $WP->version(),
			'url_home'     => $WP->homeUrl(),
			'url_wp'       => $WP->wpUrl(),
			'url_rest'     => $WP->restUrl(),
			'url_content'  => $WP->contentUrl(),
			'locale'       => $WP->locale(),
			'timezone'     => $WP->timezoneString(),
			'wplang'       => \defined( 'WPLANG' ) ? WPLANG : '',
			'is_multisite' => $WP->isMultisite(),
			'plugins'      => $WP->plugins(),
			'themes'       => $WP->themes(),
		];
	}

	protected function db() :?array {
		$DB = WorpdriveRuntime::host()->database();
		return [
			'table_prefix' => $DB->getPrefix(),
			'host_parsed'  => $DB->loadWpdb()->parse_db_host( \defined( 'DB_HOST' ) ? DB_HOST : '' ),
			'constants'    => [
				\defined( 'DB_USER' ) ? DB_USER : '-1',
				\defined( 'DB_NAME' ) ? DB_NAME : '-1',
				\defined( 'DB_HOST' ) ? DB_HOST : '-1',
			],
		];
	}

	protected function versions() :array {
		$host = WorpdriveRuntime::host();
		return [
			'php'    => \phpversion(),
			'driver' => $host->pluginVersion(),
			'wp'     => $host->wordpress()->version(),
		];
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
			if ( !WorpdriveRuntime::host()->filesystem()->canWriteToDir( WorpdriveRuntime::host()->cacheDir() ) ) {
				throw new \Exception( 'Failed to write to temp' );
			}
			$canWrite = true;
		}
		catch ( \Exception $e ) {
			$canWrite = false;
		}

		return \array_merge(
			[
				'can_memory_limit'  => \function_exists( 'wp_is_ini_value_changeable' ) ? (int)wp_is_ini_value_changeable( 'memory_limit' ) : -1,
				'can_write_dir_tmp' => (int)$canWrite,
				'can_zip_archive'   => \class_exists( '\ZipArchive' ),
				'can_zip_pcl'       => $this->canPclZip(),
				'can_app_passwords' => \function_exists( 'wp_is_application_passwords_supported' ) ? (int)wp_is_application_passwords_supported() : -1,
			],
			$this->diskSpaceChecks( $canWrite )
		);
	}

	private function diskSpaceChecks( bool $previousSuccess ) :array {
		$host = WorpdriveRuntime::host();
		$tmpDir = $host->cacheDir();
		$checks = [];
		foreach ( $this->checkParams[ 'disk_space_checks' ] ?? [ 1024 => '1kb', 1048576 => '1mb', ] as $size => $tag ) {
			$path = path_join( $tmpDir, sprintf( 'test_write_%s.txt', $tag ) );
			if ( !empty( $path ) ) {
				$previousSuccess = $previousSuccess && $host->filesystem()->writeRandomBytesFile( $path, $size );
				$checks[ 'can_write_'.$tag ] = $previousSuccess;
			}
		}
		return $checks;
	}

	private function canPclZip() :bool {
		if ( !\class_exists( '\PclZip' ) ) {
			$lib = path_join( ABSPATH, 'wp-admin/includes/class-pclzip.php' );
			if ( \is_file( $lib ) ) {
				require_once( $lib );
			}
		}
		return \class_exists( '\PclZip' );
	}

	private function ini() :array {
		$result = [];
		foreach (
			[
				'error_log',
				'max_execution_time',
			] as $ini
		) {
			$result[ $ini ] = \ini_get( $ini );
		}
		return $result;
	}

	protected function paths() :array {
		$host = WorpdriveRuntime::host();
		$WP = $host->wordpress();
		$wpContent = \defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '';
		return [
			'wp_abspath'      => trailingslashit( ABSPATH ),
			'script_filename' => $WP->scriptFilename(),
			'dir_content'     => (string)$wpContent,
			'dir_plugins'     => \defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : null,
			'dir_includes'    => path_join( $wpContent, \defined( 'WPINC' ) ? WPINC : '' ),
			'url_content'     => $WP->contentUrl(),
			'WPINC'           => \defined( 'WPINC' ) ? WPINC : null,
			'icwp_plugin_dir' => $host->rootDir(),
		];
	}
}
