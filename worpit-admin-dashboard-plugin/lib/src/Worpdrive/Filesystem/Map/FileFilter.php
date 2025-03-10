<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class FileFilter {

	private array $abs;

	private array $contains;

	private array $regEx;

	private int $newerThanTS;

	private int $olderThanTS;

	private int $maxFileSizeBytes;

	public function __construct( array $abs, array $contains, array $regEx, int $maxFileSizeMB, int $newerThanTS = 0, int $olderThanTS = 0 ) {
		$this->abs = $abs;
		$this->contains = $contains;
		$this->regEx = $regEx;
		$this->maxFileSizeBytes = $maxFileSizeMB*1024*1024;
		$this->newerThanTS = $newerThanTS;
		$this->olderThanTS = $olderThanTS;
	}

	public function isFileSizeAllowed( int $size ) :bool {
		return empty( $this->maxFileSizeBytes ) || $size < $this->maxFileSizeBytes;
	}

	public function isFileWithinTimeRange( int $mTime ) :bool {
		return ( empty( $this->newerThanTS ) || $mTime > $this->newerThanTS )
			   && ( empty( $this->olderThanTS ) || $mTime < $this->olderThanTS );
	}

	public function isExcluded( string $path ) :bool {
		$excluded = false;
		foreach ( $this->abs as $a ) {
			if ( \str_starts_with( $path, $a ) ) {
				$excluded = true;
			}
		}
		if ( !$excluded ) {
			foreach ( $this->contains as $c ) {
				if ( \str_contains( $path, $c ) ) {
					$excluded = true;
				}
			}
		}
		if ( !$excluded ) {
			foreach ( $this->regEx as $r ) {
				if ( \preg_match( $r, $path ) ) {
					$excluded = true;
				}
			}
		}
		return $excluded;
	}
}