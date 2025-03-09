<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class MapProgressTracker {

	private array $completedDirs;

	private int $totalDirsComplete;

	public function __construct( array $completedDirs = [], int $totalDirsComplete = 0 ) {
		$this->completedDirs = $completedDirs;
		$this->totalDirsComplete = $totalDirsComplete;
	}

	public function completed() :array {
		return $this->completedDirs;
	}

	public function total() :int {
		return $this->totalDirsComplete;
	}

	public function isCompleted( string $dir ) :bool {
		$dir = trailingslashit( $dir );
		$completed = isset( $this->completedDirs[ $dir ] );
		if ( !$completed ) {
			foreach ( \array_keys( $this->completedDirs ) as $completedDir ) {
				if ( \str_starts_with( $dir, $completedDir ) ) {
					$completed = true;
					break;
				}
			}
		}
		return $completed;
	}

	public function markCompleted( string $dir ) :void {
		$dir = trailingslashit( $dir );

		foreach ( \array_keys( $this->completedDirs ) as $completedDir ) {
			if ( \str_starts_with( $completedDir, $dir ) ) {
				$this->completedDirs[ $completedDir ] = false;
			}
		}

		$this->completedDirs = \array_filter( $this->completedDirs );
		$this->completedDirs[ $dir ] = true;
		$this->totalDirsComplete++;
	}
}