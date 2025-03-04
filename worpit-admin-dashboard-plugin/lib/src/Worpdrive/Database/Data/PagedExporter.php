<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Data;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Exc\TimeLimitReachedException;

class PagedExporter {

	private string $dumpFileDir;

	private ExportTracker $exportMap;

	private int $pageRowsLimit;

	private int $stopAtTS;

	public function __construct( string $dumpFileDir, int $pageRowsLimit, ExportTracker $progressTracker, int $stopAtTS ) {
		$this->dumpFileDir = $dumpFileDir;
		$this->exportMap = $progressTracker;
		$this->stopAtTS = $stopAtTS;
		$this->pageRowsLimit = $pageRowsLimit;
	}

	/**
	 * @throws TimeLimitReachedException
	 * @throws \Exception
	 */
	public function run() :void {
		foreach ( \array_filter( $this->exportMap->status(), fn( array $s ) => empty( $s[ 'completed_at' ] ) ) as $table => $status ) {
			do {
				$currentPage = (int)\floor( ( $status[ 'offset' ]*$status[ 'chunk_size' ] )/$this->pageRowsLimit ) + 1;
				$dumpFile = \fopen( $this->dumpFileFor( $table, $currentPage ), 'w' );
				try {
					$chunkExportStatus = ( new ChunkedExporter(
						$dumpFile,
						$table,
						$this->pageRowsLimit,
						$status[ 'offset' ],
						$status[ 'chunk_size' ]
					) )->run();

					$status[ 'offset' ] = $chunkExportStatus[ 'current_offset' ];
					$status[ 'completed_at' ] = $chunkExportStatus[ 'table_export_complete' ] ? \time() : 0;
					$this->exportMap->updateStatus( $table, $status );

					if ( \time() >= $this->stopAtTS ) {
						throw new TimeLimitReachedException();
					}
				}
				finally {
					if ( \is_resource( $dumpFile ) ) {
						\fclose( $dumpFile );
					}
				}
			} while ( empty( $status[ 'completed_at' ] ) );
		}
	}

	private function dumpFileFor( string $table, int $page ) :string {
		$file = path_join(
			$this->dumpFileDir,
			sprintf( 'data_%s_%s.sql',
				\preg_replace(
					sprintf( "#^%s#", \preg_quote( \ICWP_APP_WpDb::GetInstance()->getPrefix(), '#' ) ),
					'',
					$table
				),
				$page
			)
		);
		if ( \is_file( $file ) ) {
			FileSystem::Instance()->delete( $file );
		}
		return $file;
	}
}