<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing\SqliteFileListing;

class MapDir {

	private SqliteFileListing $map;

	private MapProgressTracker $tracker;

	private FileFilter $filter;

	private string $dir;

	private string $hashAlgo;

	private \FilesystemIterator $it;

	private array $dirs;

	private int $stopAtTS;

	/**
	 * @var array[]
	 */
	private array $files;

	/**
	 * @throws Exc\MapDirCannotBeOpenedException
	 */
	public function __construct(
		SqliteFileListing $map,
		MapProgressTracker $tracker,
		FileFilter $filter,
		string $dirToMap,
		string $hashAlgo,
		int $stopAtTS
	) {
		try {
			$this->it = new \FilesystemIterator( $dirToMap );
		}
		catch ( \Exception $e ) {
			throw new Exc\MapDirCannotBeOpenedException( $e->getMessage() );
		}
		$this->map = $map;
		$this->tracker = $tracker;
		$this->filter = $filter;
		$this->dir = $dirToMap;
		$this->hashAlgo = $hashAlgo;
		$this->stopAtTS = $stopAtTS;

		$this->dirs = $this->files = [];
	}

	/**
	 * @throws TimeLimitReachedException
	 */
	public function run() :void {
		$this->enum();
		foreach ( $this->dirs as $dir ) {
			try {
				( new MapDir( $this->map, $this->tracker, $this->filter, $dir, $this->hashAlgo, $this->stopAtTS ) )->run();
			}
			catch ( Exc\MapDirCannotBeOpenedException $e ) {
//				error_log( $e->getMessage() );
			}
		}
		foreach ( $this->files as $attr ) {
			$this->map->addRaw(
				$this->normalisePath( $attr[ 'p' ] ),
				'',
				empty( $this->hashAlgo ) ? '' : \hash_file( $this->hashAlgo, $attr[ 'p' ] ),
				$attr[ 'm' ],
				$attr[ 's' ],
			);
			if ( \time() >= $this->stopAtTS ) {
				throw new TimeLimitReachedException();
			}
		}

		$this->tracker->markDirCompleted( $this->normalisePath( $this->dir ) );

		if ( \time() >= $this->stopAtTS ) {
			throw new TimeLimitReachedException();
		}
	}

	private function enum() :void {
		foreach ( $this->it as $item ) {
			$absPath = $item->getPathname();
			$normalisedPath = $this->normalisePath( $absPath );
			/** @var \SplFileInfo $item */
			if ( $item->isDir() ) {
				if ( !$this->tracker->isCompleted( $normalisedPath ) && !$this->filter->isExcluded( $normalisedPath ) ) {
					$this->dirs[] = $absPath;
				}
			}
			elseif ( $item->isFile() && !$item->isLink() && !empty( $item->getSize() ) ) {
				if ( $this->filter->isFileWithinTimeRange( (int)$item->getMTime() )
					 && $this->filter->isFileSizeAllowed( $item->getSize() )
					 && !$this->filter->isExcluded( $normalisedPath )
					 && !$this->map->exists( $normalisedPath ) ) {

					$this->files[ $absPath ] = [
						'p' => $absPath,
						'm' => (int)$item->getMTime(),
						's' => $item->getSize(),
					];
				}
			}
		}
		\natsort( $this->dirs );
		\ksort( $this->files, \SORT_NATURAL );
		$this->files = \array_values( $this->files );
	}

	private function normalisePath( string $path ) :string {
		return wp_normalize_path( \ltrim( \preg_replace( '#^'.\preg_quote( ABSPATH, '#' ).'#', '', $path, 1 ), '/' ) );
	}
}