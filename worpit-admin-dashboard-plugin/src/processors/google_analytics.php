<?php

class ICWP_APP_Processor_GoogleAnalytics extends ICWP_APP_Processor_BaseApp {

	/**
	 * @var array
	 */
	private $gaOpts = null;

	public function run() {
		add_action( 'wp', [ $this, 'onWp' ] );
	}

	public function onWp() {
		$ID = $this->getTrackingId();
		if ( !empty( $ID ) && !$this->getIfIgnoreUser() && $this->isValidAnalyticsMode() ) {

			if ( $this->getAnalyticsMode() === 'sitetag' ) {
				add_action( 'wp_enqueue_scripts', function () {
					wp_register_script(
						'icontrolwp-gsitetag',
						add_query_arg( [ 'id' => $this->getTrackingId() ], 'https://www.googletagmanager.com/gtag/js' ),
						[],
						self::con()->getVersion(),
						[ 'in_footer' => false ]
					);
					wp_enqueue_script( 'icontrolwp-gsitetag' );
				} );
			}

			add_action( $this->getWpHook(), function () {
				$this->mod->displayTemplate(
					sprintf( 'snippets/analytics_%s.php', \strtolower( $this->getAnalyticsMode() ) ),
					[ 'tid' => $this->getTrackingId() ]
				);
			}, 100 );

			if ( $this->getAnalyticsMode() == 'tagman' ) {
				add_action( 'wp_body_open', function () {
					$this->mod->displayTemplate(
						'snippets/analytics_tagman_body.php',
						[ 'tid' => $this->getTrackingId() ]
					);
				} );
			}
		}
	}

	private function getAnalyticsMode() :string {
		return (string)$this->getGaOpts()[ 'analytics_mode' ];
	}

	private function getTrackingId() :string {
		return (string)$this->getGaOpts()[ 'tracking_id' ];
	}

	private function getGaOpts() :array {
		return $this->gaOpts ?? $this->gaOpts = [
			'tracking_id'            => $this->mod->getOpt( 'tracking_id' ),
			'analytics_mode'         => \strtolower( $this->mod->getOpt( 'analytics_mode' ) ),
			'ignore_logged_in_user'  => $this->mod->getOpt( 'ignore_logged_in_user' ),
			'ignore_from_user_level' => $this->mod->getOpt( 'ignore_from_user_level', 11 ),
			'in_footer'              => $this->mod->getOpt( 'in_footer' ),
		];
	}

	private function getIfIgnoreUser() :bool {
		$level = $this->loadWpUsers()->getCurrentUserLevel();
		return $this->getGaOpts()[ 'ignore_logged_in_user' ] == 'Y'
			   && $level >= 0
			   && $level >= $this->getGaOpts()[ 'ignore_from_user_level' ];
	}

	public function isValidAnalyticsMode() :bool {
		return \in_array( $this->getAnalyticsMode(), [ 'sitetag', 'tagman' ] );
	}

	private function getWpHook() :string {
		return ( $this->getAnalyticsMode() != 'tagman' && $this->getGaOpts()[ 'in_footer' ] == 'Y' ) ?
			'wp_print_footer_scripts' : 'wp_head';
	}

	/**
	 * @deprecated 4.5
	 */
	public function printGoogleAnalytics() {
	}

	/**
	 * @deprecated 4.5
	 */
	public function printTagManBody() {
	}

	/**
	 * @deprecated 4.5
	 */
	private function parseAnalyticsSnippet( string $type ) {
	}
}