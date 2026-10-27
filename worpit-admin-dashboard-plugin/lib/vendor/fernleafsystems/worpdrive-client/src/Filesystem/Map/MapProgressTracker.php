<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

class MapProgressTracker {

	private array $completedDirs;

	private int $totalDirsComplete;

	private int $totalFilesComplete;

	private int $scannedEntries;

	private ?string $mostRecentFileInDir;

	/**
	 * Name of the last entry each unfinished directory has handled, keyed by directory.
	 * @var array<string,string>
	 */
	private array $activeEntryCursors = [];

	/**
	 * @param array $activeEntryCursors base64-encoded entry names keyed by directory, as toProgressArray() saves them
	 */
	public function __construct(
		array $completedDirs = [],
		?string $mostRecentFile = null,
		int $totalDirsComplete = 0,
		int $totalFilesComplete = 0,
		array $activeEntryCursors = [],
		int $scannedEntries = 0
	) {
		$this->completedDirs = $completedDirs;
		$this->mostRecentFileInDir = $mostRecentFile;
		$this->totalDirsComplete = $totalDirsComplete;
		$this->totalFilesComplete = $totalFilesComplete;
		$this->scannedEntries = $scannedEntries;
		foreach ( $activeEntryCursors as $dir => $encodedName ) {
			// A cursor that cannot be decoded rescans its directory from the start, which can only list files twice.
			$name = \is_string( $encodedName ) ? \base64_decode( $encodedName, true ) : false;
			if ( \is_string( $name ) ) {
				$this->activeEntryCursors[ $this->normaliseDir( (string)$dir ) ] = $name;
			}
		}
	}

	public function completed() :array {
		return $this->completedDirs;
	}

	public function totalDirsComplete() :int {
		return $this->totalDirsComplete;
	}

	public function totalFilesComplete() :int {
		return $this->totalFilesComplete;
	}

	public function scannedEntries() :int {
		return $this->scannedEntries;
	}

	public function isDirCompleted( string $dir ) :bool {
		$dir = trailingslashit( $dir );
		$completed = isset( $this->completedDirs[ $dir ] );
		if ( !$completed ) {
			foreach ( \array_keys( $this->completedDirs ) as $previouslyCompletedDir ) {
				if ( \str_starts_with( $dir, $previouslyCompletedDir ) ) {
					$completed = true;
					break;
				}
			}
		}
		return $completed;
	}

	public function activeEntryCursorFor( string $dir ) :?string {
		return $this->activeEntryCursors[ $this->normaliseDir( $dir ) ] ?? null;
	}

	public function getMostRecentFile() :?string {
		return $this->mostRecentFileInDir;
	}

	public function markFileCompleted( string $file ) :void {
		$this->mostRecentFileInDir = $file;
		$this->totalFilesComplete++;
	}

	public function advanceEntryScan( string $dir, string $handledName ) :void {
		$this->activeEntryCursors[ $this->normaliseDir( $dir ) ] = $handledName;
		$this->scannedEntries++;
	}

	public function markDirCompleted( string $dir ) :void {
		$dir = trailingslashit( $dir );

		foreach ( \array_keys( $this->completedDirs ) as $previouslyCompletedDir ) {
			if ( \str_starts_with( $previouslyCompletedDir, $dir ) ) {
				$this->completedDirs[ $previouslyCompletedDir ] = false;
			}
		}

		$this->completedDirs = \array_filter( $this->completedDirs );
		$this->completedDirs[ $dir ] = true;
		$this->totalDirsComplete++;
		$this->mostRecentFileInDir = null;
		unset( $this->activeEntryCursors[ $dir ] );
	}

	/**
	 * @return array{
	 *   completed_dirs:array<string,bool>,
	 *   most_recent_file:?string,
	 *   total_completed_dirs:int,
	 *   total_completed_files:int,
	 *   active_entry_cursors:array<string,string>,
	 *   scanned_entries:int
	 * }
	 */
	public function toProgressArray() :array {
		return [
			'completed_dirs'        => $this->completed(),
			'most_recent_file'      => $this->getMostRecentFile(),
			'total_completed_dirs'  => $this->totalDirsComplete(),
			'total_completed_files' => $this->totalFilesComplete(),
			// Names are base64-encoded because JSON encoding would rewrite names that are not valid UTF-8.
			'active_entry_cursors'  => \array_map( 'base64_encode', $this->activeEntryCursors ),
			'scanned_entries'       => $this->scannedEntries(),
		];
	}

	private function normaliseDir( string $dir ) :string {
		return trailingslashit( $dir );
	}
}
