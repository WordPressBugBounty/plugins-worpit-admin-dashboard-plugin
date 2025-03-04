<?php

class ICWP_APP_WpDb {

	/**
	 * @var \ICWP_APP_WpDb
	 */
	protected static $I = null;

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	public static function GetInstance() :self {
		if ( is_null( self::$I ) ) {
			self::$I = new self();
		}
		return self::$I;
	}

	private function __construct() {
	}

	/**
	 * Given any SQL query, will perform it using the WordPress database object.
	 *
	 * @param string $sql
	 * @return int|bool (number of rows affected or just true/false)
	 */
	public function doSql( string $sql ) {
		return $this->loadWpdb()->query( $sql );
	}

	public function getPrefix() :string {
		return (string)$this->loadWpdb()->prefix;
	}

	/**
	 * @return array|null|object
	 */
	public function getResults( string $query, $format = ARRAY_A ) {
		return $this->getWpdb()->get_results( $query, $format );
	}

	/**
	 * @return array|null|object
	 */
	public function showTableStatus( $format = OBJECT ) {
		return $this->getResults( sprintf( "SHOW TABLE STATUS FROM `%s`", DB_NAME ), $format );
	}

	/**
	 * @return mixed
	 */
	public function getVar( string $sql ) {
		return $this->getWpdb()->get_var( $sql );
	}

	/**
	 * @param $table
	 * @return false|int
	 * @throws Exception
	 */
	public function optimizeTable( $table ) {
		if ( empty( $table ) ) {
			throw new Exception( 'Database table name to optimize cannot be empty.' );
		}
		return $this->doSql( sprintf( 'OPTIMIZE TABLE `%s`', esc_sql( $table ) ) );
	}

	/**
	 * @param \stdClass $table
	 */
	public function isTableView( $table ) :bool {
		return isset( $table->Comment ) && \preg_match( '/view/i', $table->Comment );
	}

	/**
	 * @param stdClass $table - as retrieved from "show tables"
	 */
	public function isTableCrashed( $table ) :bool {
		return !$this->isTableView( $table ) && is_null( $table->Rows );
	}

	/**
	 * @return \wpdb
	 */
	public function loadWpdb() :\wpdb {
		return $this->wpdb ??= $this->getWpdb();
	}

	private function getWpdb() {
		global $wpdb;
		return $wpdb;
	}
}