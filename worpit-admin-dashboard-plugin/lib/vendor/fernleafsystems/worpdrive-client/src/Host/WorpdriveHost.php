<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Host;

interface WorpdriveHost {

	public function rootDir() :string;

	public function baseArchivePath( string $uuid ) :string;

	public function pluginVersion() :string;

	public function pluginUrlForItem( string $relativePath ) :string;

	public function cacheDir() :string;

	public function uniqueId( int $length ) :string;

	public function filesystem() :WorpdriveFilesystem;

	public function database() :WorpdriveDatabase;

	public function wordpress() :WorpdriveWordPress;
}
