<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	protected function getFile() :string {
		return (string)$this->getActionParam( 'plugin_file' );
	}
}