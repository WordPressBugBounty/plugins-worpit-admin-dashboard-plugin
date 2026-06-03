<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem;

abstract class BaseFsHandler extends \FernleafSystems\WorpdriveClient\BaseHandler {

	protected string $dir;

	/**
	 * @throws \Exception
	 */
	public function __construct( string $dir, string $uuid, int $stopAtTS ) {
		parent::__construct( $uuid, $stopAtTS );
		$this->dir = trailingslashit( wp_normalize_path( $dir ) );
		$this->validate();
	}

	/**
	 * @throws \Exception
	 */
	protected function validate() :void {
		$normAbs = wp_normalize_path( ABSPATH );
		if ( $this->dir !== trailingslashit( $normAbs ) && $this->dir !== trailingslashit( \dirname( $normAbs ) ) ) {
			throw new \Exception( sprintf( "We don't currently support irregular paths (%s / %s)", $this->dir, trailingslashit( ABSPATH ) ) );
		}
	}
}