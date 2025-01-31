<?php

class ICWP_APP_WpComments extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpComments
	 */
	protected static $I = null;

	private function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ?? self::$I = new self();
	}

	/**
	 * @param array $params
	 * @return array[]
	 */
	public function getComments( $params = [] ) {
		return \array_map(
			function ( $c ) {
				return (array)$c;
			},
			get_comments( wp_parse_args( $params, $this->getDefaultLookupParams() ) )
		);
	}

	/**
	 * @param string $sType
	 * @param array  $aLookupParams
	 */
	public function getCommentsOfType( $sType, $aLookupParams = [] ) :array {
		$aLookupParams[ 'type' ] = $sType;
		return $this->getComments( $aLookupParams );
	}

	/**
	 * @param array $aCommentTypes
	 * @param array $aLookupParams
	 */
	public function getCommentsOfTypes( $aCommentTypes, $aLookupParams = [] ) :array {
		$aResults = [];
		foreach ( $aCommentTypes as $sType ) {
			$aResults = \array_merge( $aResults, $this->getCommentsOfType( $sType, $aLookupParams ) );
		}
		return $aResults;
	}

	/**
	 * @param string $nCommentId
	 * @param string $sNewStatus
	 * @return bool|WP_Error
	 */
	public function setCommentStatus( $nCommentId, $sNewStatus ) {
		$mResult = false;
		if ( in_array( $sNewStatus, [ 'hold', 'approve', 'spam', 'trash', 'delete' ] ) ) {
			$mResult = wp_set_comment_status( $nCommentId, $sNewStatus );
		}
		return is_wp_error( $mResult ) ? false : $mResult;
	}

	/**
	 * http://codex.wordpress.org/Function_Reference/get_comments
	 */
	protected function getDefaultLookupParams() :array {
		return [
			'orderby' => 'comment_date_gmt', //comment_post_ID, comment_approved, comment_ID
			'order'   => 'DESC',
			'number'  => '10', //set blank to get unlimited
			'count'   => false,
		];
	}
}