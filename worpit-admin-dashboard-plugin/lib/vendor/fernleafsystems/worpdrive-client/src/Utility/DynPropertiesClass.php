<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Utility;

class DynPropertiesClass {

	private array $raw = [];

	public function __get( string $key ) {
		return $this->raw[ $key ] ?? null;
	}

	public function __isset( string $key ) :bool {
		return \array_key_exists( $key, $this->raw );
	}

	public function __set( string $key, $value ) :void {
		$this->raw[ $key ] = $value;
	}

	public function __unset( string $key ) :void {
		unset( $this->raw[ $key ] );
	}

	public function applyFromArray( array $data, array $restrictedKeys = [] ) :self {
		if ( !empty( $restrictedKeys ) ) {
			$data = \array_intersect_key( $data, \array_flip( $restrictedKeys ) );
		}
		$this->raw = $data;
		return $this;
	}

	public function reset() :void {
		$this->raw = [];
	}

	public function getRawData() :array {
		return $this->raw;
	}

	public function getRawDataAsArray() :array {
		return $this->getRawData();
	}
}
