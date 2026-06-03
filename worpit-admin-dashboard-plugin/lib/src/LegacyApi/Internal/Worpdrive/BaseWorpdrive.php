<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Host\IControlWorpdriveHost;
use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

abstract class BaseWorpdrive extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public const DEFAULT_TIME_LIMIT = 5;

	/**
	 * @throws \Exception
	 */
	abstract protected function execHandler() :?array;

	public function process() :ApiResponse {
		$err = '';
		$start = \time();
		$startMem = \memory_get_usage( true );
		try {
			WorpdriveRuntime::setHost( new IControlWorpdriveHost() );
			$this->verifyRequiredParams();
			$status = $this->execHandler();
		}
		catch ( \Exception $e ) {
			$status = null;
			$err = $e->getMessage();
			error_log( $err );
		}
		finally {
			WorpdriveRuntime::resetHost();
		}
		return empty( $err ) ?
			$this->success( [
				'time'      => \time() - $start,
				'status'    => $status ?: [],
				'mem_usage' => [
					'start' => $startMem,
					'end'   => \memory_get_usage( true ),
				],
			] )
			: $this->fail( $err, -1 );
	}

	protected function getTimeLimit() :int {
		$timeLimit = $this->getActionParam( 'time_limit' );
		return \time() + ( empty( $timeLimit ) ? static::DEFAULT_TIME_LIMIT : $timeLimit );
	}

	/**
	 * @throws \Exception
	 */
	protected function verifyRequiredParams() :void {
		if ( empty( $this->getActionParam( 'uuid' ) ) ) {
			throw new \Exception( 'uuid param is empty' );
		}
	}
}
