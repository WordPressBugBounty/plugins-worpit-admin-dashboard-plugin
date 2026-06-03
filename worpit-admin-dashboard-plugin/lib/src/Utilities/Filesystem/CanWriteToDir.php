<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class CanWriteToDir {

	/**
	 * @throws \Exception
	 */
	public function run( string $testDir ) :void {
		$FS = FileSystem::Instance();
		$hadDir = $this->isDir( $FS, $testDir );
		$testFile = null;

		try {
			if ( !$FS->mkdir( $testDir ) || !$this->isDir( $FS, $testDir ) ) {
				throw new \Exception( sprintf( 'Failed to create directory: %s', $testDir ) );
			}
			if ( !$this->isWritable( $FS, $testDir ) ) {
				throw new \Exception( sprintf( 'The test directory is not writable: %s', $testDir ) );
			}

			$testFile = $this->uniqueProbeFile( $testDir );
			if ( !$this->touch( $FS, $testFile ) ) {
				throw new \Exception( sprintf( 'Failed to touch "%s"', $testFile ) );
			}

			$testContent = \uniqid( '#FINDME-' );
			if ( !\file_put_contents( $testFile, $testContent ) ) {
				throw new \Exception( sprintf( 'Failed to write content "%s" to "%s"', $testFile, $testContent ) );
			}
			if ( !\is_file( $testFile ) ) {
				throw new \Exception( sprintf( 'Failed to find file "%s"', $testFile ) );
			}

			$content = $FS->getContents( $testFile );
			if ( $content !== $testContent ) {
				throw new \Exception( sprintf( 'The content "%s" does not match what we wrote "%s"', $content, $testContent ) );
			}
		}
		finally {
			if ( $testFile !== null && $this->isFile( $FS, $testFile ) ) {
				$this->deleteFile( $FS, $testFile );
			}
			if ( !$hadDir && $this->isDir( $FS, $testDir ) && $FS->isDirEmpty( $testDir ) ) {
				$this->deleteEmptyDir( $testDir );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	private function uniqueProbeFile( string $testDir ) :string {
		for ( $i = 0; $i < 10; $i++ ) {
			$candidate = path_join( $testDir, \str_replace( '.', '', \uniqid( 'test_write_', true ) ) );
			if ( !\file_exists( $candidate ) ) {
				return $candidate;
			}
		}
		throw new \Exception( sprintf( 'Failed to create unique test file path in "%s"', $testDir ) );
	}

	private function isDir( FileSystem $FS, string $path ) :bool {
		return $FS->isDir( $path ) || \is_dir( $path );
	}

	private function isFile( FileSystem $FS, string $path ) :bool {
		return $FS->isFile( $path ) || \is_file( $path );
	}

	private function isWritable( FileSystem $FS, string $path ) :bool {
		$wpfs = $FS->fs();
		return $wpfs ? (bool)$wpfs->is_writable( $path ) : \is_writable( $path );
	}

	private function touch( FileSystem $FS, string $path ) :bool {
		return $FS->touch( $path ) || @\touch( $path );
	}

	private function deleteFile( FileSystem $FS, string $path ) :bool {
		return $FS->delete( $path ) || @\unlink( $path );
	}

	private function deleteEmptyDir( string $path ) :bool {
		return @\rmdir( $path );
	}
}
