<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Data;

use FernleafSystems\WorpdriveClient\Exc\TimeLimitReachedException;
use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class PagedExporter {

	private string $dumpFileDir;

	private ExportMap $exportMap;

	private int $stopAtTS;

	public function __construct( string $dumpFileDir, ExportMap $exportMap, int $stopAtTS ) {
		if ( !WorpdriveRuntime::host()->filesystem()->canWriteToDir( $dumpFileDir ) ) {
			throw new \InvalidArgumentException( 'Dump file directory is not writable.' );
		}
		$this->dumpFileDir = $dumpFileDir;
		$this->exportMap = $exportMap;
		$this->stopAtTS = $stopAtTS;
	}

	/**
	 * @throws TimeLimitReachedException
	 * @throws \Exception
	 */
	public function run() :void {
		foreach ( \array_filter( $this->exportMap->status(), fn( array $s ) => empty( $s[ 'completed_at' ] ) ) as $table => $status ) {
			do {
				$dumpFile = @\fopen( $this->dumpFileFor( $table, $status[ 'page' ] ), 'w' );
				if ( !\is_resource( $dumpFile ) ) {
					throw new \Exception( \sprintf( 'Failed to open dump file for table: %s', $table ) );
				}
				try {
					$chunkExportStatus = ( new ChunkedExporter(
						$dumpFile,
						$table,
						$status[ 'offset' ],
						$status[ 'max_page_rows' ],
						$status[ 'chunk_size' ],
						$status[ 'cursor' ] ?? null,
						$status[ 'exported_rows' ]
					) )->run();

					$status[ 'offset' ] = $chunkExportStatus[ 'current_offset' ];
					$status[ 'page' ]++;
					$status[ 'completed_at' ] = $chunkExportStatus[ 'table_export_complete' ] ? \time() : 0;
					$status[ 'exported_rows' ] += $chunkExportStatus[ 'exported_rows' ];
					if ( ( $chunkExportStatus[ 'cursor' ] ?? null ) === null ) {
						unset( $status[ 'cursor' ] );
					}
					else {
						$status[ 'cursor' ] = $chunkExportStatus[ 'cursor' ];
					}
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
				$this->tableFileSlug( $table ),
				$page
			)
		);
		if ( \is_file( $file ) ) {
			WorpdriveRuntime::host()->filesystem()->deleteFile( $file );
		}
		return $file;
	}

	private function tableFileSlug( string $table ) :string {
		$unprefixed = (string)\preg_replace(
			sprintf( "#^%s#", \preg_quote( WorpdriveRuntime::host()->database()->getPrefix(), '#' ) ),
			'',
			$table,
			1
		);
		$slug = (string)\preg_replace( '#[^A-Za-z0-9_-]+#', '_', $unprefixed );
		$slug = \trim( $slug, '_-' );
		if ( $slug === '' ) {
			$slug = 'table';
		}
		if ( $slug !== $unprefixed ) {
			$slug .= '_'.\substr( \hash( 'sha256', $unprefixed ), 0, 8 );
		}
		return $slug;
	}
}
