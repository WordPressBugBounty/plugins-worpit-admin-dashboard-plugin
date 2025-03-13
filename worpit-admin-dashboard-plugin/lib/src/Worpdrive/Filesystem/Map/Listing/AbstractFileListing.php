<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing;

abstract class AbstractFileListing extends \FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\AbstractPagedIterator\AbstractPagedIterator implements FileListing {

	public function add( FileListItem $item ) :void {
		$this->addRaw( $item->path, $item->hash, $item->hash_alt, $item->mtime, $item->size );
	}

	public function startLargeListing() :void {
	}

	public function finishLargeListing( bool $successfulCreation ) :void {
	}

	public function current() :FileListItem {
		return parent::current();
	}

	abstract public function getNewestItem() :?FileListItem;

	public function getTotalSize() :int {
		return $this->total();
	}

	public function createClone( bool $transientClone = true ) :AbstractFileListing {
		return clone $this;
	}

	protected function normalisePath( string $path ) :string {
		return \ltrim( $path, '/' );
	}
}