<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Functions;

function get_plugin() :\ICWP_Plugin {
	global $g_oWorpit;
	return empty( $g_oWorpit ) ? \ICWP_Plugin::GetInstance() : $g_oWorpit;
}