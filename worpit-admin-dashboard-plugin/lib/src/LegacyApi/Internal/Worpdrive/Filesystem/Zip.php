<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Zip\ZipHandler;

class Zip extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	public function process() :ApiResponse {
		$err = '';
		$start = \time();
		try {
			if ( empty( $this->getActionParam( 'dir' ) ) ) {
				throw new \Exception( 'Dir param is empty' );
			}
			if ( empty( $this->getActionParam( 'uuid' ) ) ) {
				throw new \Exception( 'uuid param is empty' );
			}
			if ( empty( $this->getActionParam( 'file_paths' ) ) || !\is_array( $this->getActionParam( 'file_paths' ) ) ) {
				throw new \Exception( 'File paths to zip was empty.' );
			}

			$status = ( new ZipHandler(
				$this->getActionParam( 'uuid' ),
				$this->getTimeLimit(),
				$this->getActionParam( 'dir' ),
				\array_map( '\base64_decode', $this->getActionParam( 'file_paths' ) ),
			) )->run();
		}
		catch ( \Exception $e ) {
			$err = $e->getMessage();
		}

		return empty( $err ) ?
			$this->success( [
				'time'   => \time() - $start,
				'status' => $status ?? null,
			] )
			: $this->fail( $err, -1 );
	}
}