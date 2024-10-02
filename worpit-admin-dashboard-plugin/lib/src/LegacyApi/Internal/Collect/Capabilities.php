<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\File\ZipDir;

class Capabilities extends Base {

	public function process() :ApiResponse {
		return $this->success( [ 'capabilities' => $this->collect() ] );
	}

	public function collect() :array {
		$DP = $this->loadDP();
		$canExtensionLoaded = \function_exists( 'extension_loaded' ) && \is_callable( 'extension_loaded' );
		return [
			'php_version'                => $DP->getPhpVersion(), //TODO DELETE
			'version_php'                => $DP->getPhpVersion(),
			'is_force_ssl_admin'         => ( function_exists( 'force_ssl_admin' ) && force_ssl_admin() ) ? 1 : 0,
			'can_handshake'              => $this->isHandshakeEnabled() ? 1 : 0,
			'can_handshake_openssl'      => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			'can_wordpress_write'        => $this->checkCanWordpressWrite() ? 1 : 0,
			'ext_pdo'                    => class_exists( 'PDO' ) || ( $canExtensionLoaded && extension_loaded( 'pdo' ) ),
			'ext_mysqli'                 => ( $canExtensionLoaded && extension_loaded( 'mysqli' ) ) ? 1 : 0,
			'can_zip'                    => ZipDir::IsSupported() ? 1 : 0,
		];
	}

	protected function isHandshakeEnabled() :bool {
		return apply_filters(
			'icwp-app-CanHandshake',
			\ICWP_Plugin::getController()->loadCorePluginFeatureHandler()->getCanHandshake()
		);
	}
}