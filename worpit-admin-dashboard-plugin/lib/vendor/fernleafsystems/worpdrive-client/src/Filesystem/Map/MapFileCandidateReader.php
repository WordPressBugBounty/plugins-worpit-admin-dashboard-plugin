<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

class MapFileCandidateReader {

	private FileFilter $filter;

	private MapPathNormalizer $pathNormalizer;

	private string $rootDir;

	public function __construct( FileFilter $filter, MapPathNormalizer $pathNormalizer, string $rootDir ) {
		$this->filter = $filter;
		$this->pathNormalizer = $pathNormalizer;
		$this->rootDir = $rootDir;
	}

	public function read( \SplFileInfo $item ) :?MapFileCandidate {
		try {
			if ( !$item->isFile() || $item->isLink() ) {
				return null;
			}

			$size = $item->getSize();
			if ( empty( $size ) ) {
				return null;
			}

			$path = $item->getPathname();
			$normalisedPath = $this->pathNormalizer->normaliseRelativeToRoot( $path, $this->rootDir );
			$mTime = (int)$item->getMTime();
			if ( !$this->filter->isFileWithinTimeRange( $mTime )
				 || !$this->filter->isFileSizeAllowed( $size )
				 || $this->filter->isExcluded( $normalisedPath ) ) {
				return null;
			}

			return new MapFileCandidate( $path, $normalisedPath, $mTime, $size );
		}
		catch ( \Exception $e ) {
			return null;
		}
	}
}
