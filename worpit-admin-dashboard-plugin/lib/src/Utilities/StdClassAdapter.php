<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities;

trait StdClassAdapter {

	/**
	 * @var array
	 */
	private $aRaw;

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {
		$data = $this->getRawDataAsArray();
		return $data[ $sProperty ] ?? null;
	}

	/**
	 * @param string $sProperty
	 * @return bool
	 */
	public function __isset( $sProperty ) {
		return array_key_exists( $sProperty, $this->getRawDataAsArray() );
	}

	/**
	 * @param string $sProperty
	 * @param mixed  $mValue
	 * @return $this
	 */
	public function __set( $sProperty, $mValue ) {
		$aA = $this->getRawDataAsArray();
		$aA[ $sProperty ] = $mValue;
		return $this->applyFromArray( $aA );
	}

	/**
	 * @param string $sKey
	 * @return $this
	 */
	public function __unset( $sKey ) {
		$aD = $this->getRawDataAsArray();
		if ( array_key_exists( $sKey, $aD ) ) {
			unset( $aD[ $sKey ] );
			$this->applyFromArray( $aD );
		}
		return $this;
	}

	/**
	 * @param array $aDataValues associative with parameter keys and values
	 * @param array $aRestrictedKeys
	 * @return $this
	 */
	public function applyFromArray( $aDataValues, $aRestrictedKeys = array() ) {
		if ( !empty( $aRestrictedKeys ) ) {
			$aDataValues = array_intersect_key( $aDataValues, array_flip( $aRestrictedKeys ) );
		}
		$this->aRaw = $aDataValues;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getRawDataAsArray() {
		if ( !is_array( $this->aRaw ) ) {
			$this->aRaw = array();
		}
		return $this->aRaw;
	}
}