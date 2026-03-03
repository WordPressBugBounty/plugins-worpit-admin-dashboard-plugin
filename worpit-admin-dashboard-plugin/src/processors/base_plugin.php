<?php

class ICWP_APP_Processor_BasePlugin extends ICWP_APP_Processor_BaseApp {

	public function init() {
	}

	public function run() {
	}

	/**
	 * @param array $attr
	 * @return bool
	 */
	protected function getIfDisplayAdminNotice( $attr ) :bool {
		if ( !parent::getIfDisplayAdminNotice( $attr ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @deprecated 4.5
	 */
	public function getIsShowMarketing() :bool {
		return false;
	}

	/**
	 * @deprecated 4.5
	 */
	protected function getInstallationDays() :int {
		return 0;
	}

	/**
	 * @deprecated 4.5
	 */
	public function getIsDeleteOnDeactivate() :bool {
		return false;
	}
}