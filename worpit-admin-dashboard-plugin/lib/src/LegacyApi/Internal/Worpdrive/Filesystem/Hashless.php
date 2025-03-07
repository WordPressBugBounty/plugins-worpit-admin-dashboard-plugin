<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\{
	HashlessMapHandler,
	MapVO
};

class Hashless extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	public function process() :ApiResponse {
		$err = '';
		$start = \time();
		$startMem = \memory_get_usage( true );
		try {
			if ( empty( $this->getActionParam( 'dir' ) ) ) {
				throw new \Exception( 'Dir param is empty' );
			}
			if ( empty( $this->getActionParam( 'uuid' ) ) ) {
				throw new \Exception( 'uuid param is empty' );
			}
			if ( empty( $this->getActionParam( 'file_exclusions' ) ) || !\is_array( $this->getActionParam( 'file_exclusions' ) ) ) {
				throw new \Exception( "There's no scenario where there are no exclusions." );
			}

			$mapVO = new MapVO();
			$mapVO->dir = $this->getActionParam( 'dir' );
			$mapVO->exclusions = $this->getActionParam( 'file_exclusions' );
			$mapVO->hashAlgo = '';

			$status = ( new HashlessMapHandler(
				$mapVO,
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