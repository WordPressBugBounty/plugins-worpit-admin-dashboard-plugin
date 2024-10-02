<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Theme;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	protected function getFile() :string {
		return (string)$this->getActionParam( 'theme_file' );
	}
}