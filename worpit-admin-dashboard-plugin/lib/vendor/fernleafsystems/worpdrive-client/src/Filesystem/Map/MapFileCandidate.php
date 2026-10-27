<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

class MapFileCandidate {

	private string $sourcePath;

	private string $normalisedPath;

	private int $mtime;

	private int $size;

	public function __construct( string $sourcePath, string $normalisedPath, int $mtime, int $size ) {
		$this->sourcePath = $sourcePath;
		$this->normalisedPath = $normalisedPath;
		$this->mtime = $mtime;
		$this->size = $size;
	}

	public function sourcePath() :string {
		return $this->sourcePath;
	}

	public function normalisedPath() :string {
		return $this->normalisedPath;
	}

	public function mtime() :int {
		return $this->mtime;
	}

	public function size() :int {
		return $this->size;
	}
}
