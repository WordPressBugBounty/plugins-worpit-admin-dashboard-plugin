<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;

class MapHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\BaseFsHandler {

	protected MapVO $mapVO;

	protected FileExclude $excluder;

	/**
	 * @throws \Exception
	 */
	public function __construct( MapVO $mapVO, string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS, $mapVO->dir );

		$this->mapVO = $mapVO;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$completed = false;

		$this->excluder = new FileExclude(
			\array_map( fn( $ex ) => path_join( ABSPATH, \base64_decode( $ex ) ), $this->mapVO->exclusions[ 'abs' ] ?? [] ),
			\array_map( '\base64_decode', $this->mapVO->exclusions[ 'contains' ] ?? [] ),
			\array_map( '\base64_decode', $this->mapVO->exclusions[ 'regex' ] ?? [] ),
			$this->mapVO->maxFileSize,
			$this->mapVO->newerThanTS,
			$this->mapVO->olderThanTS
		);

		$map = new Listing\SqliteFileListing( $this->pathToDB() );
		$track = $this->loadProgress();
		$mapper = new MapDir( $map, $track, $this->excluder, $this->mapVO->dir, $this->mapVO->hashAlgo, $this->stopAtTS );
		try {
			$map->startLargeListing();
			$mapper->run();
			$map->finishLargeListing( true );
			$completed = true;
		}
		catch ( TimeLimitReachedException $e ) {
			$map->finishLargeListing( true );
			FileSystem::Instance()->putFileContents(
				$this->pathToProgress(),
				wp_json_encode( [
					'completed_dirs'       => $track->completed(),
					'total_completed_dirs' => $track->total(),
				] )
			);
		}
		catch ( \Exception $e ) {
			$map->finishLargeListing( false );
			throw $e;
		}

		return [
			'href'                 => $completed ? $this->mapURL() : '',
			'completed_dirs'       => \count( $track->completed() ),
			'total_completed_dirs' => $track->total(),
			'map_count'            => $map->count(),
		];
	}

	protected function dbFile() :string {
		return 'map.sqlite';
	}

	protected function pathToDB() :string {
		return path_join( $this->workingDir(), $this->dbFile() );
	}

	protected function pathToProgress() :string {
		return path_join( $this->workingDir(), $this->dbFile().'_progress.json' );
	}

	/**
	 * @throws \Exception
	 */
	private function loadProgress() :MapProgressTracker {
		$progress = [];
		$total = 0;
		if ( \is_file( $this->pathToProgress() ) ) {
			$raw = FileSystem::Instance()->getContents( $this->pathToProgress() );
			if ( !empty( $raw ) ) {
				$rawProgress = \json_decode( $raw, true );
				if ( !empty( $rawProgress ) && \is_array( $rawProgress ) ) {
					[ 'completed_dirs' => $progress, 'total_completed_dirs' => $total ] = $rawProgress;
				}
			}
		}
		return new MapProgressTracker( $progress, $total );
	}

	private function mapURL() :string {
		return remove_query_arg(
			'ver',
			self::con()
				->getPluginUrl( sprintf( '%s/%s', untrailingslashit( $this->baseArchivePath() ), $this->dbFile() ) ),
		);
	}
}