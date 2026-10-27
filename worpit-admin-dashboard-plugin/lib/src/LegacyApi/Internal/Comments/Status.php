<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Comments;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class Status extends LegacyApi\Internal\Base {

	public function process() :LegacyApi\ApiResponse {
		$results = [];
		foreach ( $this->getActionParam( 'comments_and_status' ) as $nCommentId => $sStatus ) {
			$results[ $nCommentId ] = $this->loadWpCommentsProcessor()
										   ->setCommentStatus( $nCommentId, $sStatus );
		}

		return $this->success( [
			'results' => $results
		] );
	}
}