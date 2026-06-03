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

	private function normaliseDataStatus( array $status ) :array {
		if ( !isset( $status[ 'href' ] ) || !\is_string( $status[ 'href' ] ) ) {
			$status[ 'href' ] = '';
		}

		if ( !isset( $status[ 'table_export_map' ] ) || !\is_array( $status[ 'table_export_map' ] ) ) {
			$status[ 'table_export_map' ] = [];
		}

		if ( empty( $status[ 'table_export_map' ] ) ) {
			$contextMap = $status[ 'error_context' ][ 'table_export_map' ] ?? null;
			if ( \is_array( $contextMap ) && $this->isValidTableExportMap( $contextMap ) ) {
				$status[ 'table_export_map' ] = $contextMap;
			}
		}

		return $status;
	}

	private function isValidTableExportMap( array $map ) :bool {
		if ( empty( $map ) ) {
			return false;
		}

		foreach ( $map as $table => $status ) {
			if ( !\is_string( $table ) || !\is_array( $status ) ) {
				return false;
			}
			foreach ( [ 'offset', 'page', 'completed_at', 'exported_rows', 'max_page_rows', 'chunk_size' ] as $key ) {
				if ( !isset( $status[ $key ] ) || !\is_int( $status[ $key ] ) ) {
					return false;
				}
			}
		}
		return true;
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
