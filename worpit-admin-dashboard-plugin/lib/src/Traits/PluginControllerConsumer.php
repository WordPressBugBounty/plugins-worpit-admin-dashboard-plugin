<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Traits;

use FernleafSystems\Wordpress\Plugin\iControlWP\Controller;

/**
 * @deprecated 4.5
 */
trait PluginControllerConsumer {

	private $con;

	/**
	 * @var Controller
	 */
	private $oPlugCon;

	/**
	 * @return Controller
	 */
	public function getCon() {
		return $this->con ?? $this->oPlugCon;
	}

	/**
	 * @param Controller $con
	 * @return $this
	 */
	public function setCon( $con ) {
		$this->con = $con;
		return $this;
	}
}