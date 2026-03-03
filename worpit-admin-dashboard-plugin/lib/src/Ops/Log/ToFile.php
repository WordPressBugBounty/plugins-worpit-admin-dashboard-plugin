<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Ops\Log;

use function FernleafSystems\Wordpress\Plugin\iControlWP\Functions\get_plugin;

class ToFile extends \ICWP_APP_Foundation {

	public static function LogIt( string $content, string $logFileSuffix = '' ) :bool {
		$file = path_join(
			get_plugin()->getController()->getRootDir(),
			sprintf( 'tmp/tmplog%s.txt', empty( $logFileSuffix ) ? '' : '-'.$logFileSuffix )
		);
		return !empty( file_put_contents( $file, $content."\n", FILE_APPEND ) );
	}
}