<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Control;

trait PluginControllerConsumer {

	/**
	 * @since 5.0
	 */
	public static function con() :Controller {
		return \FernleafSystems\Wordpress\Plugin\iControlWP\Functions\get_plugin()->getController();
	}
}