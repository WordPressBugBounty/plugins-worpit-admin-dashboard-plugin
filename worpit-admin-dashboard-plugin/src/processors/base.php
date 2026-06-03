<?php

abstract class ICWP_APP_Processor_Base extends ICWP_APP_Foundation {

	/**
	 * @var \ICWP_APP_FeatureHandler_Base
	 */
	protected $mod;

	/**
	 * @var \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\RequestParameters
	 */
	protected $reqParams;

	/**
	 * @param \ICWP_APP_FeatureHandler_Base $mod
	 */
	public function __construct( $mod ) {
		$this->mod = $mod;
	}

	/**
	 * Override to set what this processor does when it's "run"
	 */
	abstract public function run();
}