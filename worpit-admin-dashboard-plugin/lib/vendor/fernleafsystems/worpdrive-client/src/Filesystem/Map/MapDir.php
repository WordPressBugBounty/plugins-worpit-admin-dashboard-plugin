<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

use FernleafSystems\WorpdriveClient\Exc\TimeLimitReachedException;
use FernleafSystems\WorpdriveClient\Filesystem\Map\Listing\AbstractFileListing;
use FernleafSystems\WorpdriveClient\Host\{
	WorpdriveFilesystem,
	WorpdriveRuntime
};

class MapDir {

	private AbstractFileListing $map;

	private MapProgressTracker $tracker;

	private FileFilter $filter;

	private WorpdriveFilesystem $filesystem;

	private MapPathNormalizer $pathNormalizer;

	private MapFileCandidateReader $candidateReader;

	private string $dir;

	private string $rootDir;

	private string $hashAlgo;

	private int $stopAtTS;

	public function __construct(
		AbstractFileListing $map,
		MapProgressTracker $tracker,
		FileFilter $filter,
		string $dirToMap,
		string $hashAlgo,
		int $stopAtTS,
		?string $rootDir = null
	) {
		$this->map = $map;
		$this->tracker = $tracker;
		$this->filter = $filter;
		$this->filesystem = WorpdriveRuntime::host()->filesystem();
		$this->pathNormalizer = new MapPathNormalizer();
		$this->dir = $this->pathNormalizer->normalisePathSeparators( $dirToMap );
		$this->rootDir = $this->pathNormalizer->normalisePathSeparators( $rootDir ?? $dirToMap );
		$this->candidateReader = new MapFileCandidateReader( $this->filter, $this->pathNormalizer, $this->rootDir );
		$this->hashAlgo = $hashAlgo;
		$this->stopAtTS = $stopAtTS;
	}

	/**
	 * @throws TimeLimitReachedException
	 * @throws Exc\MapDirCannotBeOpenedException
	 */
	public function run() :void {
		$this->mapEntries();

		// No time check after this: the parent checks once its cursor has moved past this directory.
		$this->tracker->markDirCompleted( $this->normalisePath( $this->dir ) );
	}

	/**
	 * Entries are handled in byte order of their names, and each directory on the active path saves the name of the
	 * last entry it handled. A resumed request skips names up to that cursor, so files added or deleted between
	 * requests can't move it, and a child directory still in progress sorts after its parent's cursor and is entered
	 * again. Each directory on the active path holds its sorted names while it's mapped: about 12 MB per 100,000 entries.
	 *
	 * @throws Exc\MapDirCannotBeOpenedException
	 * @throws TimeLimitReachedException
	 */
	private function mapEntries() :void {
		$normalDir = $this->normalisePath( $this->dir );
		$cursor = $this->tracker->activeEntryCursorFor( $normalDir );

		foreach ( $this->entryNames() as $name ) {
			if ( $cursor !== null && \strcmp( $name, $cursor ) <= 0 ) {
				continue;
			}
			$this->mapEntry( new \SplFileInfo( \rtrim( $this->dir, '/' ).'/'.$name ) );
			$this->tracker->advanceEntryScan( $normalDir, $name );
			if ( \time() >= $this->stopAtTS ) {
				throw new TimeLimitReachedException();
			}
		}
	}

	/**
	 * @return string[] entry names in byte order
	 * @throws Exc\MapDirCannotBeOpenedException
	 */
	private function entryNames() :array {
		$names = [];
		try {
			foreach ( new \FilesystemIterator( $this->dir, \FilesystemIterator::SKIP_DOTS ) as $item ) {
				/** @var \SplFileInfo $item */
				$names[] = $item->getFilename();
			}
		}
		catch ( \Exception $e ) {
			throw new Exc\MapDirCannotBeOpenedException( $e->getMessage() );
		}
		\sort( $names, \SORT_STRING );
		return $names;
	}

	/**
	 * @throws TimeLimitReachedException
	 */
	private function mapEntry( \SplFileInfo $item ) :void {
		try {
			$isDir = $item->isDir();
			$isLink = $item->isLink();
		}
		catch ( \Exception $e ) {
			// Some hosts expose unreadable entries; skip them and keep mapping.
			return;
		}

		if ( $isDir && !$isLink ) {
			try {
				$path = $item->getPathname();
				$normalisedPath = $this->normalisePath( $path );
			}
			catch ( \Exception $e ) {
				// Some hosts expose unreadable entries; skip them and keep mapping.
				return;
			}
			if ( !$this->tracker->isDirCompleted( $normalisedPath ) && !$this->filter->isExcluded( $normalisedPath ) ) {
				try {
					( new MapDir( $this->map, $this->tracker, $this->filter, $path, $this->hashAlgo, $this->stopAtTS, $this->rootDir ) )->run();
				}
				catch ( Exc\MapDirCannotBeOpenedException $e ) {
					// Keep mapping best-effort when a child directory cannot be opened.
				}
			}
			return;
		}

		$this->mapFile( $item );
	}

	private function mapFile( \SplFileInfo $item ) :void {
		$candidate = $this->candidateReader->read( $item );
		if ( $candidate === null ) {
			return;
		}

		if ( $this->filesystem->isReadable( $candidate->sourcePath() ) ) {
			$hash = empty( $this->hashAlgo ) ? '' : \hash_file( $this->hashAlgo, $candidate->sourcePath() );
			if ( \is_string( $hash ) ) {
				$this->map->addRaw(
					$candidate->normalisedPath(),
					'',
					$hash,
					$candidate->mtime(),
					$candidate->size()
				);
			}
		}
		$this->tracker->markFileCompleted( $candidate->normalisedPath() );
	}

	private function normalisePath( string $path ) :string {
		return $this->pathNormalizer->normaliseRelativeToRoot( $path, $this->rootDir );
	}
}
