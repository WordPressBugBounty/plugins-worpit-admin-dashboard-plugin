<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Environment extends Base {

	public function process() :ApiResponse {
		return $this->success( [ 'capabilities' => $this->collect() ] );
	}

	public function collect() :array {
		if ( \function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 15 );
		}

		$appsData = [];
		if ( \function_exists( 'exec' ) ) {
			$appsData = $this->collectApplicationVersions( [
				'mysql -V',
				'unzip -v',
				'zip -v',
				'tar --version'
			] );
		}

		return [
			'open_basedir'    => \ini_get( 'open_basedir' ),
			'can_exec'        => 0,
			'can_timelimit'   => $this->testCanTimeLimit() ? 1 : 0,
			'can_write'       => $this->checkCanWrite() ? 1 : 0,
			'can_zip'         => ( $appsData[ 'zip' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_unzip'       => ( $appsData[ 'unzip' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_mysql'       => ( $appsData[ 'mysql' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'applications'    => $appsData,
		];
	}

	protected function testCanTimeLimit() :bool {
		if ( !\function_exists( 'set_time_limit' ) ) {
			return false;
		}
		$new = \ini_get( 'max_execution_time' ) + 30;
		@\set_time_limit( $new );
		return \ini_get( 'max_execution_time' ) == $new;
	}

	protected function collectApplicationVersions( array $appVersionCmds ) :array {
		$apps = [];

		foreach ( $appVersionCmds as $versionCmd ) {
			list( $exec, $execParams ) = \explode( ' ', $versionCmd, 2 );
			@\exec( $versionCmd, $output, $nReturnVal );

			$apps[ $exec ] = [
				'exec'         => $exec,
				'version-cmd'  => $versionCmd,
				'found'        => $nReturnVal === 0,
			];
		}
		return $apps;
	}
}