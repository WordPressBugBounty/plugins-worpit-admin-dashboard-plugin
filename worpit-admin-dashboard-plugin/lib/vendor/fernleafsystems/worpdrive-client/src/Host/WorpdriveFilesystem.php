<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Host;

interface WorpdriveFilesystem {

	public function mkdir( $path ) :bool;

	public function delete( $path );

	public function deleteFile( $path );

	public function deleteDir( $path );

	public function enumItemsInDir( string $dir ) :array;

	public function putFileContent( $path, $contents, bool $compress = false ) :bool;

	public function getFileContent( $path );

	public function isFile( $path ) :bool;

	public function isReadable( string $path ) :bool;

	public function mtime( string $path ) :int;

	public function size( string $path ) :int;

	public function canWriteToDir( string $dir ) :bool;

	public function writeRandomBytesFile( string $path, int $size ) :bool;
}
