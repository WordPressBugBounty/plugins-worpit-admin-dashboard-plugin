<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\MapVO;

class Recent extends BaseMap {

	protected function getMapType() :string {
		return 'recent';
	}

	protected function getMapVO() :MapVO {
		$mapVO = parent::getMapVO();
		$mapVO->newerThanTS = (int)$this->getActionParam( 'newer_than_ts' );
		return $mapVO;
	}

	/**
	 * @throws \Exception
	 */
	protected function verifyRequiredParams() :void {
		parent::verifyRequiredParams();
		if ( $this->getActionParam( 'newer_than_ts' ) === null ) {
			throw new \Exception( "newer_than_ts param isn't provided." );
		}
	}
}