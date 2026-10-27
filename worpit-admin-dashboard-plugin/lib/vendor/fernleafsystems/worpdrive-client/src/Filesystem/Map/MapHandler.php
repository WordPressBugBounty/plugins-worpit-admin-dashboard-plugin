<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

use FernleafSystems\WorpdriveClient\Exc\TimeLimitReachedException;
use FernleafSystems\WorpdriveClient\Filesystem\Map\Listing\AbstractFileListing;
use FernleafSystems\WorpdriveClient\Utility\{
	Base64PayloadDecoder,
	FileNameFor
};

class MapHandler extends \FernleafSystems\WorpdriveClient\Filesystem\BaseFsHandler {

	protected MapVO $mapVO;

	protected FileFilter $filter;

	private AbstractFileListing $map;

	private bool $wpCfgRemapped = false;

	/**
	 * @throws \Exception
	 */
	public function __construct( MapVO $mapVO, string $uuid, int $stopAtTS ) {
		parent::__construct( $mapVO->dir, $uuid, $stopAtTS );
		$this->mapVO = $mapVO;
	}

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$completed = false;
		$payloadDecoder = new Base64PayloadDecoder();

		$this->filter = new FileFilter(
			\array_merge(
				$payloadDecoder->decodeOptionalList( $this->mapVO->exclusions[ 'contains' ] ?? [] ),
				[
					\str_replace( wp_normalize_path( ABSPATH ), '', \dirname( $this->workingDir() ) ),
				]
			),
			$payloadDecoder->decodeOptionalList( $this->mapVO->exclusions[ 'regex' ] ?? [] ),
			$this->mapVO->maxFileSize,
			$this->mapVO->newerThanTS,
			$this->mapVO->olderThanTS
		);

		$map = $this->map();
		$track = $this->loadProgress();
		$mapper = new MapDir( $map, $track, $this->filter, $this->dir, $this->mapVO->hashAlgo, $this->stopAtTS, $this->dir );
		try {
			$map->startLargeListing();

			$mapper->run();
			// WP Config mapping is done only after completion of the full map, since we don't want it duplicated
			$this->mapForWpConfig();

			$map->finishLargeListing( true );
			$completed = true;
		}
		catch ( TimeLimitReachedException $e ) {
			$map->finishLargeListing( true );
			$this->host()->filesystem()->putFileContent(
				$this->pathToProgress(),
				wp_json_encode( $track->toProgressArray() )
			);
		}
		catch ( \Exception $e ) {
			$map->finishLargeListing( false );
			throw $e;
		}

		return [
			'href'                 => $completed ? $this->mapURL() : '',
			'completed_dirs'       => \count( $track->completed() ),
			'total_completed_dirs' => $track->totalDirsComplete(),
			'map_count'            => $track->totalFilesComplete(),
			'scanned_entries'      => $track->scannedEntries(),
			'latest_file'          => $track->getMostRecentFile(),
			'wpcfg_remapped'       => (int)$this->wpCfgRemapped,
		];
	}

	protected function map() :AbstractFileListing {
		return $this->map ??= $this->useSqlite() ?
			new Listing\SqliteFileListing( path_join( $this->workingDir(), $this->dbFile() ) )
			: new Listing\FlatFileListing( path_join( $this->workingDir(), $this->dbFile() ) );
	}

	protected function mapForWpConfig() :void {
		$possibleDirs = \array_unique( \array_map(
			fn( $path ) => trailingslashit( wp_normalize_path( $path ) ),
			[
				ABSPATH,
				$this->mapVO->dir,
			]
		) );

		$stdPathFound = null;
		foreach ( $possibleDirs as $possibleDir ) {
			$maybeStdPath = path_join( $possibleDir, 'wp-config.php' );
			if ( \file_exists( $maybeStdPath ) ) {
				$stdPathFound = $maybeStdPath;
				break;
			}
		}

		$normalAbs = wp_normalize_path( ABSPATH );
		if ( empty( $stdPathFound ) && !empty( \dirname( $normalAbs ) ) ) {
			$levelUpPath = path_join( \dirname( $normalAbs ), 'wp-config.php' );
			if ( \is_readable( $levelUpPath ) ) {
				$FS = $this->host()->filesystem();
				$this->map()->addRaw(
					'wp-config.php',
					'',
					empty( $this->mapVO->hashAlgo ) ? '' : (string)\hash_file( $this->mapVO->hashAlgo, $levelUpPath ),
					$FS->mtime( $levelUpPath ),
					$FS->size( $levelUpPath )
				);
				$this->wpCfgRemapped = true;
			}
		}
	}

	protected function dbFile() :string {
		return FileNameFor::For( $this->mapType().'_map_db' );
	}

	protected function useSqlite() :bool {
		return \in_array( 'sqlite3', \get_loaded_extensions() );
	}

	protected function mapType() :string {
		return $this->mapVO->type;
	}

	protected function pathToProgress() :string {
		return path_join( $this->workingDir(), FileNameFor::For( $this->mapType().'_map_progress' ) );
	}

	/**
	 * @throws \Exception
	 */
	private function loadProgress() :MapProgressTracker {
		$dirsCompleted = [];
		$mostRecentFile = null;
		$totalDirs = $totalFiles = $scannedEntries = 0;
		$activeEntryCursors = [];
		if ( \is_file( $this->pathToProgress() ) ) {
			$raw = $this->host()->filesystem()->getFileContent( $this->pathToProgress() );
			if ( !empty( $raw ) ) {
				$rawProgress = \json_decode( $raw, true );
				// Progress from before per-directory entry cursors (0.4.0 and earlier) can't be resumed safely, so the map starts again.
				if ( \is_array( $rawProgress ) && \is_array( $rawProgress[ 'active_entry_cursors' ] ?? null ) ) {
					$dirsCompleted = \is_array( $rawProgress[ 'completed_dirs' ] ?? null ) ? $rawProgress[ 'completed_dirs' ] : [];
					$mostRecentFile = \is_string( $rawProgress[ 'most_recent_file' ] ?? null ) ? $rawProgress[ 'most_recent_file' ] : null;
					$totalDirs = \max( 0, (int)( $rawProgress[ 'total_completed_dirs' ] ?? 0 ) );
					$totalFiles = \max( 0, (int)( $rawProgress[ 'total_completed_files' ] ?? 0 ) );
					$activeEntryCursors = $rawProgress[ 'active_entry_cursors' ];
					$scannedEntries = \max( 0, (int)( $rawProgress[ 'scanned_entries' ] ?? 0 ) );
				}
			}
		}
		return new MapProgressTracker( $dirsCompleted, $mostRecentFile, $totalDirs, $totalFiles, $activeEntryCursors, $scannedEntries );
	}

	private function mapURL() :string {
		return $this->host()->pluginUrlForItem(
			sprintf( '%s/%s', untrailingslashit( $this->baseArchivePath() ), $this->dbFile() )
		);
	}
}
