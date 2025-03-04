<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive;

abstract class BaseWorpdrive extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	public const DEFAULT_TIME_LIMIT = 5;

	protected function getTimeLimit() :int {
		return \time() + ( empty( $this->getActionParam( 'time_limit' ) ) ? static::DEFAULT_TIME_LIMIT : $this->getActionParam( 'time_limit' ) );
	}
}