<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class CanWriteToDir {

	/**
	 * @throws \Exception
	 */
	public function run( string $testDir ) :void {
		$FS = FileSystem::Instance();

		if ( !$FS->mkdir( $testDir ) || !$FS->isDir( $testDir ) ) {
			throw new \Exception( sprintf( 'Failed to create directory: %s', $testDir ) );
		}
		if ( !$FS->fs()->is_writable( $testDir ) ) {
			throw new \Exception( sprintf( 'The test directory is not writable: %s', $testDir ) );
		}

		$testFile = path_join( $testDir, 'test_write' );
		if ( !$FS->touch( $testDir ) ) {
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
		$FS->delete( $testDir );
	}
}