<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Worpdrive\Host;

use FernleafSystems\WorpdriveClient\Host\WorpdriveDatabase;

class IControlWorpdriveDatabase implements WorpdriveDatabase {

	/**
	 * The network's base prefix by default, as Shield reports, so a multisite's database backup holds every site's
	 * tables even when the request is answered on a subsite, whose own prefix (`wp_2_`) would cover only its tables.
	 */
	public function getPrefix( bool $siteBase = true ) :string {
		return $siteBase ? (string)$this->db()->loadWpdb()->base_prefix : $this->db()->getPrefix();
	}

	public function loadWpdb() {
		return $this->db()->loadWpdb();
	}

	public function selectCustom( $query, $format = null ) {
		return $format === null ?
			$this->db()->getResults( (string)$query )
			: $this->db()->getResults( (string)$query, $format );
	}

	public function doSql( string $sql ) {
		return $this->db()->doSql( $sql );
	}

	public function getVar( $query ) {
		return $this->db()->getVar( (string)$query );
	}

	public function showTableStatus( $format = null ) :array {
		$status = $format === null ? $this->db()->showTableStatus() : $this->db()->showTableStatus( $format );
		return \is_array( $status ) ? $status : [];
	}

	private function db() :\ICWP_APP_WpDb {
		return \ICWP_APP_WpDb::GetInstance();
	}
}
