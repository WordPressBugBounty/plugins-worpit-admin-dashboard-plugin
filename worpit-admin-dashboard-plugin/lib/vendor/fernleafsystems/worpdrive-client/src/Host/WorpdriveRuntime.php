<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Host;

class WorpdriveRuntime {

	private static ?WorpdriveHost $host = null;

	public static function setHost( WorpdriveHost $host ) :void {
		self::$host = $host;
	}

	public static function withHost( WorpdriveHost $host, callable $callback ) {
		self::setHost( $host );
		try {
			return $callback();
		}
		finally {
			self::resetHost();
		}
	}

	public static function host() :WorpdriveHost {
		if ( self::$host === null ) {
			throw new \RuntimeException( 'WorpDrive host runtime has not been configured.' );
		}
		return self::$host;
	}

	public static function resetHost() :void {
		self::$host = null;
	}
}
