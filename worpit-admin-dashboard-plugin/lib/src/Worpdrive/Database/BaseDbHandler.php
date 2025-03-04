<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database;

class BaseDbHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\BaseHandler {

	/**
	 * @throws \Exception
	 */
	public function __construct( string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS );
	}
}