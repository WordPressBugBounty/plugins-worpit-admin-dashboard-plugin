<?php declare( strict_types=1 );

use FernleafSystems\Wordpress\Plugin\iControlWP\Functions;

if ( \function_exists( 'icontrolwp_get_plugin' ) ) {
	return;
}

function icontrolwp_get_plugin() :\ICWP_Plugin {
	return Functions\get_plugin();
}