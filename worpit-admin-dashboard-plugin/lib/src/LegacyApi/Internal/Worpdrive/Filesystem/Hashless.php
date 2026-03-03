<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Filesystem;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\MapVO;

class Hashless extends BaseMap {

	protected function getMapType() :string {
		return 'hashless';
	}

	protected function getMapVO() :MapVO {
		$mapVO = parent::getMapVO();
		$mapVO->hashAlgo = '';
		return $mapVO;
	}
}