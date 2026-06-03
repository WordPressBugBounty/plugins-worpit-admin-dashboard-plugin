<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\WorpdriveClient\Filesystem\Zip\ZipHandler;
use FernleafSystems\WorpdriveClient\Utility\Base64PayloadDecoder;

class Zip extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new ZipHandler(
			( new Base64PayloadDecoder() )->decodeRequiredList( $this->getActionParam( 'file_paths' ) ),
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
		$filePaths = $this->getActionParam( 'file_paths' );
		if ( empty( $filePaths ) || !\is_array( $filePaths ) ) {
			throw new \Exception( 'File paths to zip was empty.' );
		}
	}
}
