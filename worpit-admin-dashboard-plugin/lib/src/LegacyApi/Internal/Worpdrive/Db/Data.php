<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Db;

use FernleafSystems\WorpdriveClient\Database\Data\DataExportHandler;

class Data extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	protected function execHandler() :?array {
		return $this->normaliseDataStatus( $this->runDataExportHandler() );
	}

	protected function runDataExportHandler() :array {
		return ( new DataExportHandler(
			$this->getActionParam( 'table_export_map' ),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit(),
		) )->run();
	}

	/**
	 * A failed request's `error_context` is passed on untouched and never used as the map: the failed request made
	 * no archive, so none of its progress reaches WorpDrive, and WorpDrive resends the map it sent.
	 */
	private function normaliseDataStatus( array $status ) :array {
		if ( !isset( $status[ 'href' ] ) || !\is_string( $status[ 'href' ] ) ) {
			$status[ 'href' ] = '';
		}

		if ( !isset( $status[ 'table_export_map' ] ) || !\is_array( $status[ 'table_export_map' ] ) ) {
			$status[ 'table_export_map' ] = [];
		}

		return $status;
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
