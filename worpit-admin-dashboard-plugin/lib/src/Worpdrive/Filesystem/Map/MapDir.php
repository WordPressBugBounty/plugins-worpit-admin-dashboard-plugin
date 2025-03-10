<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing\SqliteFileListing;

class MapDir {

	private SqliteFileListing $map;

	private MapProgressTracker $tracker;

	private FileFilter $excluder;

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
		FileFilter $excluder,
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
		$this->dir = $dirToMap;
		$this->hashAlgo = $hashAlgo;
		$this->tracker = $tracker;
		$this->map = $map;
		$this->stopAtTS = $stopAtTS;
		$this->excluder = $excluder;

		$this->dirs = $this->files = [];
	}

	/**
	 * @throws TimeLimitReachedException
	 */
	public function run() :void {
		$this->enum();
		foreach ( $this->dirs as $dir ) {
			try {
				( new MapDir( $this->map, $this->tracker, $this->excluder, $dir, $this->hashAlgo, $this->stopAtTS ) )->run();
			}
			catch ( Exc\MapDirCannotBeOpenedException $e ) {
//				error_log( $e->getMessage() );
			}
		}
		foreach ( $this->files as $attr ) {
			$this->map->addRaw(
				$attr[ 'p' ],
				'',
				empty( $this->hashAlgo ) ? '' : \hash_file( $this->hashAlgo, path_join( ABSPATH, $attr[ 'p' ] ) ),
				$attr[ 'm' ],
				$attr[ 's' ],
			);
		}

		$this->tracker->markCompleted( $this->dir );

		if ( \time() >= $this->stopAtTS ) {
			throw new TimeLimitReachedException();
		}
	}

	private function enum() :void {
		foreach ( $this->it as $item ) {
			/** @var \SplFileInfo $item */
			if ( $item->isDir() && !$this->tracker->isCompleted( $item->getPathname() ) ) {
				$path = $this->normalisePath( $item->getPathname() );
				if ( !$this->excluder->isExcluded( $path ) ) {
					$this->dirs[] = $path;
				}
			}
			elseif ( $item->isFile() && !$item->isLink() && !empty( $item->getSize() ) ) {
				$path = $this->normalisePath( $item->getPathname() );
				if ( $this->excluder->isFileWithinTimeRange( (int)$item->getMTime() )
					 && $this->excluder->isFileSizeAllowed( $item->getSize() )
					 && !$this->excluder->isExcluded( $path ) ) {
					$this->files[ $item->getPathname() ] = [
						'p' => $this->normalisePath( $item->getPathname() ),
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