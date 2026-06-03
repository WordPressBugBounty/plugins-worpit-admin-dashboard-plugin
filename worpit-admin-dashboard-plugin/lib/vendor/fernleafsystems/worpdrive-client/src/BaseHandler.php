<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient;

use FernleafSystems\WorpdriveClient\Host\{
	WorpdriveHost,
	WorpdriveRuntime
};

abstract class BaseHandler {

	protected string $uuid;

	protected int $stopAtTS;

	private ?string $pathWorkingDir = null;

	public function __construct( string $uuid, int $stopAtTS ) {
		$this->uuid = $uuid;
		$this->stopAtTS = $stopAtTS;
	}

	/**
	 * @throws \Exception
	 */
	abstract public function run() :array;

	protected function workingDir() :string {
		if ( empty( $this->pathWorkingDir ) ) {
			$this->pathWorkingDir = trailingslashit( wp_normalize_path(
				path_join( $this->host()->rootDir(), $this->baseArchivePath() )
			) );
			$this->host()->filesystem()->mkdir( $this->pathWorkingDir );
		}
		return $this->pathWorkingDir;
	}

	protected function baseArchivePath() :string {
		return $this->host()->baseArchivePath( $this->uuid );
	}

	protected function host() :WorpdriveHost {
		return WorpdriveRuntime::host();
	}
}
