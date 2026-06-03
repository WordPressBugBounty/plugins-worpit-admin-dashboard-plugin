<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\WorpdriveClient\Download as WorpdriveDownload;
use FernleafSystems\WorpdriveClient\Utility\EnumTypes;

class Download extends BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new WorpdriveDownload(
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
		$downloadType = $this->getActionParam( 'download_type' );
		if ( empty( $downloadType ) ) {
			throw new \Exception( 'download_type param is empty' );
		}
		if ( !\in_array( $downloadType, ( new EnumTypes() )->downloads() ) ) {
			throw new \Exception( sprintf( 'Invalid download type "%s"', $downloadType ) );
		}
	}
}
