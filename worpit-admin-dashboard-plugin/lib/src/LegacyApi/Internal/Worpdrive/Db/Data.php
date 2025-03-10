<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Db;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Data\DataExportHandler;

class Data extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return ( new DataExportHandler(
			$this->getActionParam( 'table_export_map' ),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit(),
		) )->run();
	}

	/**
	 * @inheritDoc
	 */
	protected function verifyRequiredParams() :void {
		parent::verifyRequiredParams();
		if ( empty( $this->getActionParam( 'table_export_map' ) ) ) {
			throw new \Exception( 'table_export_map param is empty' );
		}
	}
}