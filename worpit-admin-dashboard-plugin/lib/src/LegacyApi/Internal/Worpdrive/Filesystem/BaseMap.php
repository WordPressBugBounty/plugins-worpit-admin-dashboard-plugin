<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\WorpdriveClient\Filesystem\Map\{
	MapHandler,
	MapVO
};

abstract class BaseMap extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\BaseWorpdrive {

	abstract protected function getMapType() :string;

	protected function execHandler() :?array {
		return ( new MapHandler(
			$this->getMapVO(),
			$this->getActionParam( 'uuid' ),
			$this->getTimeLimit(),
		) )->run();
	}

	protected function getMapVO() :MapVO {
		$mapVO = new MapVO();
		$mapVO->type = $this->getMapType();
		$mapVO->dir = $this->getActionParam( 'dir' );
		$mapVO->exclusions = $this->getActionParam( 'file_exclusions' );
		return $mapVO;
	}

	/**
	 * @throws \Exception
	 */
	protected function verifyRequiredParams() :void {
		parent::verifyRequiredParams();
		if ( empty( $this->getActionParam( 'dir' ) ) ) {
			throw new \Exception( 'Dir param is empty' );
		}
		$fileExclusions = $this->getActionParam( 'file_exclusions' );
		if ( empty( $fileExclusions ) || !\is_array( $fileExclusions ) ) {
			throw new \Exception( "There's no scenario where there are no exclusions." );
		}
	}
}
