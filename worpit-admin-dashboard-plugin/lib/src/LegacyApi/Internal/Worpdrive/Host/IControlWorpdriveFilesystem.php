<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Host;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\Filesystem\CanWriteToDir;
use FernleafSystems\WorpdriveClient\Host\WorpdriveFilesystem;

class IControlWorpdriveFilesystem implements WorpdriveFilesystem {

	public function mkdir( $path ) :bool {
		return FileSystem::Instance()->mkdir( (string)$path );
	}

	public function delete( $path ) {
		return FileSystem::Instance()->delete( (string)$path );
	}

	public function deleteFile( $path ) {
		return FileSystem::Instance()->delete( (string)$path );
	}

	public function deleteDir( $path ) {
		return FileSystem::Instance()->delete( (string)$path );
	}

	public function enumItemsInDir( string $dir ) :array {
		return FileSystem::Instance()->enumItemsInDir( $dir );
	}

	public function putFileContent( $path, $contents, bool $compress = false ) :bool {
		return FileSystem::Instance()->putFileContents( (string)$path, (string)$contents );
	}

	public function getFileContent( $path ) {
		return FileSystem::Instance()->getContents( (string)$path );
	}

	public function isFile( $path ) :bool {
		return FileSystem::Instance()->isFile( (string)$path );
	}

	public function isReadable( string $path ) :bool {
		$fs = FileSystem::Instance()->fs();
		return $fs ? (bool)$fs->is_readable( $path ) : \is_readable( $path );
	}

	public function mtime( string $path ) :int {
		return (int)\ICWP_APP_WpFilesystem::GetInstance()->getModifiedTime( $path );
	}

	public function size( string $path ) :int {
		return (int)\ICWP_APP_WpFilesystem::GetInstance()->getFileSize( $path );
	}

	public function canWriteToDir( string $dir ) :bool {
		try {
			( new CanWriteToDir() )->run( $dir );
			return true;
		}
		catch ( \Exception $e ) {
			return false;
		}
	}

	public function writeRandomBytesFile( string $path, int $size ) :bool {
		$FS = FileSystem::Instance();
		$tmpDir = \dirname( $path );
		$hadDir = $this->pathIsDir( $FS, $tmpDir );
		$created = false;
		$deleted = false;
		try {
			$probePath = \file_exists( $path ) ? $this->uniqueSiblingProbePath( $path ) : $path;
			$created = $FS->createDummyDataFileRandomBytes( $probePath, $size );
			$deleted = $created && ( !$this->pathIsFile( $FS, $probePath ) || $this->deleteOwnedFile( $FS, $probePath ) );
		}
		catch ( \Exception $e ) {
		}
		finally {
			if ( !$hadDir && $this->pathIsDir( $FS, $tmpDir ) && $FS->isDirEmpty( $tmpDir ) ) {
				$this->deleteOwnedEmptyDir( $tmpDir );
			}
		}
		return $created && $deleted;
	}

	/**
	 * @throws \Exception
	 */
	private function uniqueSiblingProbePath( string $path ) :string {
		$dir = \dirname( $path );
		$base = \basename( $path );
		for ( $i = 0; $i < 10; $i++ ) {
			$candidate = path_join( $dir, $base.'.probe-'.\str_replace( '.', '', \uniqid( '', true ) ) );
			if ( !\file_exists( $candidate ) ) {
				return $candidate;
			}
		}
		throw new \Exception( sprintf( 'Failed to create unique probe path beside "%s"', $path ) );
	}

	private function pathIsDir( FileSystem $FS, string $path ) :bool {
		return $FS->isDir( $path ) || \is_dir( $path );
	}

	private function pathIsFile( FileSystem $FS, string $path ) :bool {
		return $FS->isFile( $path ) || \is_file( $path );
	}

	private function deleteOwnedFile( FileSystem $FS, string $path ) :bool {
		return $FS->delete( $path ) || @\unlink( $path );
	}

	private function deleteOwnedEmptyDir( string $path ) :bool {
		return @\rmdir( $path );
	}
}
