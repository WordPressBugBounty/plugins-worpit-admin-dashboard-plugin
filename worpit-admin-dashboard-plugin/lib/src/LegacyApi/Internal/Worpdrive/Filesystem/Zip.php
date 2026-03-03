<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Zip\ZipHandler;

class Zip extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new ZipHandler(
			\array_map( '\base64_decode', $this->getActionParam( 'file_paths' ) ),
			$this->getActionParam( 'dir' ),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit(),
		) )->run();
	}

	/**
	 * @throws \Exception
	 */
	protected function verifyRequiredParams() :void {
		parent::verifyRequiredParams();
		if ( empty( $this->getActionParam( 'dir' ) ) ) {
			throw new \Exception( 'Dir param is empty' );
		}
		if ( empty( $this->getActionParam( 'file_paths' ) ) || !\is_array( $this->getActionParam( 'file_paths' ) ) ) {
			throw new \Exception( 'File paths to zip was empty.' );
		}
	}
}