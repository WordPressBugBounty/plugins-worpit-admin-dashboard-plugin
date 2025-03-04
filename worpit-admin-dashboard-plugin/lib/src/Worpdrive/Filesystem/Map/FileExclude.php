<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class FileExclude {

	private array $abs;

	private array $contains;

	private array $regEx;

	public function __construct( array $abs, array $contains, array $regEx ) {
		$this->abs = $abs;
		$this->contains = $contains;
		$this->regEx = $regEx;
	}

	public function isExcluded( string $path ) :bool {
		$excluded = false;
		foreach ( $this->abs as $a ) {
			if ( \str_starts_with( $path, $a ) ) {
				$excluded = true;
			}
		}
		if ( !$excluded ) {
			foreach ( $this->contains as $c ) {
				if ( \str_contains( $path, $c ) ) {
					$excluded = true;
				}
			}
		}
		if ( !$excluded ) {
			foreach ( $this->regEx as $r ) {
				if ( \preg_match( $r, $path ) ) {
					$excluded = true;
				}
			}
		}
		return $excluded;
	}
}