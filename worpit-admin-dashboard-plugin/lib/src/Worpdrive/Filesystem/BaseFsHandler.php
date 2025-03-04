<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem;

class BaseFsHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\BaseHandler {

	protected string $dir;

	/**
	 * @throws \Exception
	 */
	public function __construct( string $uuid, int $stopAtTS, string $dir ) {
		parent::__construct( $uuid, $stopAtTS );
		$this->dir = trailingslashit( wp_normalize_path( $dir ) );
		$this->validate();
	}

	/**
	 * @throws \Exception
	 */
	protected function validate() :void {
		if ( $this->dir !== trailingslashit( wp_normalize_path( ABSPATH ) ) ) {
			throw new \Exception( sprintf( "We don't currently support irregular paths (%s / %s)", $this->dir, trailingslashit( ABSPATH ) ) );
		}
	}
}