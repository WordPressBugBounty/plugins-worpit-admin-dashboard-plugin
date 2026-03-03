<?php

abstract class ICWP_APP_Processor_Base extends ICWP_APP_Foundation {

	/**
	 * @var \ICWP_APP_FeatureHandler_Base
	 */
	protected $mod;

	/**
	 * @var \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\RequestParameters
	 */
	protected $reqParams;

	/**
	 * @param \ICWP_APP_FeatureHandler_Base $mod
	 */
	public function __construct( $mod ) {
		$this->mod = $mod;
		$this->init();
	}

	/**
	 * @param array $attr
	 */
	protected function getIfDisplayAdminNotice( $attr ) :bool {
		$notices = $this->loadAdminNoticesProcessor();

		if ( empty( $attr[ 'schedule' ] ) || !in_array( $attr[ 'schedule' ], [
				'once',
				'conditions',
				'version'
			] ) ) {
			$attr[ 'schedule' ] = 'conditions';
		}

		if ( $attr[ 'schedule' ] == 'once' &&
			 ( !$this->loadWpUsers()
					 ->getCanAddUpdateCurrentUserMeta() || $notices->getAdminNoticeIsDismissed( $attr[ 'id' ] ) )
		) {
			return false;
		}

		if ( $attr[ 'schedule' ] == 'version' && ( $this->mod->getVersion() == $notices->getAdminNoticeMeta( $attr[ 'id' ] ) ) ) {
			return false;
		}

		if ( isset( $attr[ 'type' ] )
			 && $attr[ 'type' ] == 'promo' && $this->loadWP()->getIsMobile() ) {
			return false;
		}

		return true;
	}

	public function init() {
	}

	/**
	 * Override to set what this processor does when it's "run"
	 */
	abstract public function run();

	/**
	 * @param       $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed
	 * @deprecated 4.5
	 */
	public function getOption( $sOptionKey, $mDefault = false ) {
		return $this->mod->getOpt( $sOptionKey, $mDefault );
	}
}