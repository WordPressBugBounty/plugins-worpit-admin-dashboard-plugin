<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive;

class Download extends BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new Worpdrive\Download(
			$this->getActionParam( 'download_type' ),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit()
		) )->run();
	}

	/**
	 * @inheritDoc
	 */
	protected function verifyRequiredParams() :void {
		parent::verifyRequiredParams();
		if ( empty( $this->getActionParam( 'download_type' ) ) ) {
			throw new \Exception( 'download_type param is empty' );
		}
		if ( !\in_array( $this->getActionParam( 'download_type' ), ( new Worpdrive\Enum\DownloadTypes() )->allTypes() ) ) {
			throw new \Exception( sprintf( 'Invalid download type "%s"', $this->getActionParam( 'download_type' ) ) );
		}
	}
}