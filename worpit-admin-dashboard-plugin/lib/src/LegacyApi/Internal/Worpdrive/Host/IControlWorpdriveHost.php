<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Host;

use FernleafSystems\Wordpress\Plugin\iControlWP\Control\PluginControllerConsumer;
use FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\PasswordGenerator;
use FernleafSystems\WorpdriveClient\Host\{
	WorpdriveDatabase,
	WorpdriveFilesystem,
	WorpdriveHost,
	WorpdriveWordPress
};

class IControlWorpdriveHost implements WorpdriveHost {

	use PluginControllerConsumer;

	private ?WorpdriveFilesystem $filesystem = null;

	private ?WorpdriveDatabase $database = null;

	private ?WorpdriveWordPress $wordpress = null;

	public function rootDir() :string {
		return self::con()->getRootDir();
	}

	public function baseArchivePath( string $uuid ) :string {
		return \sprintf( '%s/archive-%s/', self::con()->getPluginSpec_Path( 'temp' ), $uuid );
	}

	public function pluginVersion() :string {
		return self::con()->getVersion();
	}

	public function pluginUrlForItem( string $relativePath ) :string {
		return remove_query_arg( 'ver', self::con()->getPluginUrl( $relativePath ) );
	}

	public function cacheDir() :string {
		return (string)self::con()->getPath_Temp( 'test_write_dir' );
	}

	public function uniqueId( int $length ) :string {
		return PasswordGenerator::Uniqid( $length );
	}

	public function filesystem() :WorpdriveFilesystem {
		return $this->filesystem ??= new IControlWorpdriveFilesystem();
	}

	public function database() :WorpdriveDatabase {
		return $this->database ??= new IControlWorpdriveDatabase();
	}

	public function wordpress() :WorpdriveWordPress {
		return $this->wordpress ??= new IControlWorpdriveWordPress();
	}
}
