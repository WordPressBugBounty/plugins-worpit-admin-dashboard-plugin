<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing;

interface FileListing {

	public function add( FileListItem $item ) :void;

	public function addRaw( string $path, string $hash, string $hashAlt = '', ?int $mtime = null, ?int $size = null ) :void;

	public function get( string $path ) :?FileListItem;

	/**
	 * @param string[] $paths
	 * @return FileListItem[]
	 */
	public function m_get( array $paths ) :array;

	/**
	 * @param string[] $paths
	 */
	public function m_remove( array $paths ) :bool;

	public function remove( string $path ) :bool;

	public function exists( string $path ) :bool;

	public function total() :int;

	public function totalFileSize() :int;
}