<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Db;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Data\DataExportHandler;

class Data extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	public function process() :ApiResponse {
		$err = '';
		$start = \time();
		$startMem = \memory_get_usage( true );
		try {
			if ( empty( $this->getActionParam( 'uuid' ) ) ) {
				throw new \Exception( 'uuid param is empty' );
			}
			if ( empty( $this->getActionParam( 'table_export_map' ) ) ) {
				throw new \Exception( 'table_export_map param is empty' );
			}

			$status = ( new DataExportHandler(
				$this->getActionParam( 'table_export_map' ),
				$this->getActionParam( 'uuid' ),
				$this->getTimeLimit(),
			) )->run();
		}
		catch ( \Exception $e ) {
			$err = $e->getMessage();
			error_log( $err );
		}
		return empty( $err ) ?
			$this->success( [
				'time'      => \time() - $start,
				'status'    => $status ?? null,
				'mem_usage' => [
					'start' => $startMem,
					'end'   => \memory_get_usage( true ),
				],
			] )
			: $this->fail( $err, -1 );
	}
}