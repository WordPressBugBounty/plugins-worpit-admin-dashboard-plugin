<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Host;

interface WorpdriveDatabase {

	public function getPrefix( bool $siteBase = true ) :string;

	public function loadWpdb();

	public function selectCustom( $query, $format = null );

	public function doSql( string $sql );

	public function getVar( $query );

	public function showTableStatus( $format = null ) :array;
}
