<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Database\Operators;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class SqlDumpValueEscaper {

	public function escape( $value ) :string {
		$wpdb = WorpdriveRuntime::host()->database()->loadWpdb();

		return "'".$wpdb->remove_placeholder_escape(
			$wpdb->_real_escape( (string)$value )
		)."'";
	}
}
