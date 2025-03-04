<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;

class MapHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\BaseFsHandler {

	private FileExclude $exclude;

	/**
	 * @throws \Exception
	 */
	public function __construct( string $uuid, int $stopAtTS, string $dir, array $rawExclusions ) {
		parent::__construct( $uuid, $stopAtTS, $dir );
		$this->exclude = new FileExclude(
			\array_map( fn( $ex ) => path_join( ABSPATH, \base64_decode( $ex ) ), $rawExclusions[ 'abs' ] ?? [] ),
			\array_map( '\base64_decode', $rawExclusions[ 'contains' ] ?? [] ),
			\array_map( '\base64_decode', $rawExclusions[ 'regex' ] ?? [] ),
		);
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$completed = false;

		$map = new Listing\SqliteFileListing( path_join( $this->workingDir(), 'map.sqlite' ) );
		$track = new MapProgressTracker( $this->loadProgress() );
		$mapper = new MapDir( $map, $track, $this->exclude, $this->dir, $this->stopAtTS );
		try {
			$map->startLargeListing();
			$mapper->run();
			$map->finishLargeListing( true );
			$completed = true;
		}
		catch ( TimeLimitReachedException $e ) {
			$map->finishLargeListing( true );
			// we "save" our state
			FileSystem::Instance()
					  ->putFileContents( path_join( $this->workingDir(), 'dir_tracker.json' ), wp_json_encode( $track->completed() ) );
		}
		catch ( \Exception $e ) {
			$map->finishLargeListing( false );
			throw $e;
		}

		return [
			'href'           => $completed ? $this->mapURL() : '',
			'completed_dirs' => \count( $track->completed() ),
			'map_count'      => $map->count(),
		];
	}

	private function loadProgress() :array {
		$tracker = path_join( $this->workingDir(), 'dir_tracker.json' );
		$progress = [];
		if ( \is_file( $tracker ) ) {
			$raw = FileSystem::Instance()->getContents( $tracker );
			if ( !empty( $raw ) ) {
				$progress = \json_decode( $raw, true );
			}
		}
		return \is_array( $progress ) ? $progress : [];
	}

	private function mapURL() :string {
		return remove_query_arg(
			'ver',
			self::con()->getPluginUrl( sprintf( '%s/map.sqlite', untrailingslashit( $this->baseArchivePath() ) ) ),
		);
	}
}