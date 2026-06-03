<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map\Listing;

abstract class AbstractFileListing implements FileListing {

	protected string $listingPath;

	public function __construct( string $listingPath ) {
		$this->listingPath = $listingPath;
	}

	abstract public function startLargeListing() :void;

	abstract public function finishLargeListing( bool $successfulCreation ) :void;

	protected function normalisePath( string $path ) :string {
		return \ltrim( $path, '/' );
	}
}