<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive;

use FernleafSystems\Wordpress\Plugin\iControlWP\Control\PluginControllerConsumer;
use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

abstract class BaseHandler {

	use PluginControllerConsumer;

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
				path_join( self::con()->getRootDir(), $this->baseArchivePath() )
			) );
			FileSystem::Instance()->mkdir( $this->pathWorkingDir );
		}
		return $this->pathWorkingDir;
	}

	protected function baseArchivePath() :string {
		$con = self::con();
		return sprintf( '%s/archive-%s/', $con->getPluginSpec_Path( 'temp' ), $this->uuid );
	}
}