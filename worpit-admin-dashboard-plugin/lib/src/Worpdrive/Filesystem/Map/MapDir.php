<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing\SqliteFileListing;

class MapDir {

	private SqliteFileListing $map;

	private MapProgressTracker $progressTracker;

	private FileExclude $excluder;

	private string $dir;

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
	public function __construct( SqliteFileListing $map, MapProgressTracker $progressTracker, FileExclude $excluder, string $dirToMap, int $stopAtTS ) {
		try {
			$this->it = new \FilesystemIterator( $dirToMap );
		}
		catch ( \Exception $e ) {
			throw new Exc\MapDirCannotBeOpenedException( $e->getMessage() );
		}
		$this->dir = $dirToMap;
		$this->progressTracker = $progressTracker;
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
				( new MapDir( $this->map, $this->progressTracker, $this->excluder, $dir, $this->stopAtTS ) )->run();
			}
			catch ( Exc\MapDirCannotBeOpenedException $e ) {
//				error_log( $e->getMessage() );
			}
		}
		foreach ( $this->files as $attr ) {
			$this->map->addRaw(
				$attr[ 'p' ],
				'',
				\hash_file( 'adler32', path_join( ABSPATH, $attr[ 'p' ] ) ),
				$attr[ 'm' ],
				$attr[ 's' ],
			);
		}

		$this->progressTracker->markCompleted( $this->dir );

		if ( \time() >= $this->stopAtTS ) {
			throw new TimeLimitReachedException();
		}
	}

	private function enum() :void {
		foreach ( $this->it as $item ) {
			/** @var \SplFileInfo $item */
			if ( $item->isDir() && !$this->progressTracker->isCompleted( $item->getPathname() ) ) {
				$path = $this->normalisePath( $item->getPathname() );
				if ( !$this->excluder->isExcluded( $path ) ) {
					$this->dirs[] = $path;
				}
			}
			elseif ( $item->isFile() && !$item->isLink() && $item->getSize() > 0 ) {
				$path = $this->normalisePath( $item->getPathname() );
				if ( !$this->excluder->isExcluded( $path ) ) {
					$this->files[ $item->getPathname() ] = [
						'p' => $this->normalisePath( $item->getPathname() ),
						'm' => $item->getMTime(),
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