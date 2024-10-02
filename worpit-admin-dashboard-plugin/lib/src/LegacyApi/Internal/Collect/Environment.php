<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Environment extends Base {

	public function process() :ApiResponse {
		return $this->success( [ 'capabilities' => $this->collect() ] );
	}

	public function collect() :array {
		$DP = $this->loadDP();
		if ( \function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 15 );
		}

		$appsData = [];
		if ( \function_exists( 'exec' ) ) {
			$appsData = $this->collectApplicationVersions( [
				'mysql -V',
				'mysqldump -V',
				'mysqlimport -V',
				'unzip -v',
				'zip -v',
				'tar --version'
			] );
		}

		return [
			'open_basedir'    => \ini_get( 'open_basedir' ),
			'can_exec'        => $DP->checkCanExec() ? 1 : 0,
			'can_timelimit'   => $DP->checkCanTimeLimit() ? 1 : 0,
			'can_write'       => $this->checkCanWrite() ? 1 : 0,
			'can_tar'         => ( $appsData[ 'tar' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_zip'         => ( $appsData[ 'zip' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_unzip'       => ( $appsData[ 'unzip' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_mysql'       => ( $appsData[ 'mysql' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_mysqldump'   => ( $appsData[ 'mysqldump' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'can_mysqlimport' => ( $appsData[ 'mysqlimport' ][ 'version-info' ] ?? 0 ) > 0 ? 1 : 0,
			'applications'    => $appsData,
		];
	}

	protected function collectApplicationVersions( array $appVersionCmds ) :array {
		$apps = [];

		foreach ( $appVersionCmds as $versionCmd ) {
			list( $exec, $execParams ) = \explode( ' ', $versionCmd, 2 );
			@\exec( $versionCmd, $output, $nReturnVal );

			$apps[ $exec ] = [
				'exec'         => $exec,
				'version-cmd'  => $versionCmd,
				'version-info' => $this->parseApplicationVersionOutput( $exec, is_array( $output ) ? implode( "\n", $output ) : '' ),
				'found'        => $nReturnVal === 0,
			];
		}
		return $apps;
	}

	/**
	 * @param string $versionOutput
	 */
	protected function parseApplicationVersionOutput( string $executable, $versionOutput ) :string {
		$regExprs = [
			'mysql'       => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysql  Ver 14.14 Distrib 5.1.56, for pc-linux-gnu (i686) using readline 5.1
			'mysqlimport' => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysqlimport  Ver 3.7 Distrib 5.1.41, for Win32 (ia32)
			'mysqldump'   => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysqldump  Ver 10.13 Distrib 5.1.41, for Win32 (ia32)
			'zip'         => '/Zip\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//This is Zip 2.31 (March 8th 2005), by Info-ZIP.
			'unzip'       => '/UnZip\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//UnZip 5.52 of 28 February 2005, by Info-ZIP.  Maintained by C. Spieler.  Send
			'tar'         => '/tar\s+\(GNU\s+tar\)\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i'
			//tar (GNU tar) 1.15.1
		];

		if ( !preg_match( $regExprs[ $executable ], $versionOutput, $matches ) ) {
			return '-3';
		}
		else {
			return $matches[ 1 ];
		}
	}
}