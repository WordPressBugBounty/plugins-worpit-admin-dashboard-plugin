<?php

class ICWP_APP_WpAdminNotices extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpAdminNotices
	 */
	protected static $I = null;

	protected $notices = [];

	/**
	 * @var string
	 */
	protected $flash;

	/**
	 * @var string
	 */
	protected $sActionPrefix = '';

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	protected function __construct() {
		add_action( 'admin_notices', [ $this, 'onWpAdminNotices' ] );
		add_action( 'network_admin_notices', [ $this, 'onWpAdminNotices' ] );
		add_action( 'wp_loaded', [ $this, 'flushFlashMessage' ] );
	}

	public function onWpAdminNotices() {
		$this->flashNotice();
	}

	/**
	 * @param string $noticeID
	 */
	public function getAdminNoticeIsDismissed( $noticeID ) :bool {
		return $this->getAdminNoticeMeta( $noticeID ) == 'Y';
	}

	/**
	 * @param string $noticeID
	 * @return false|string
	 */
	public function getAdminNoticeMeta( $noticeID ) {
		return $this->loadWpUsers()->getUserMeta( $this->getActionPrefix().$noticeID );
	}

	/**
	 * @return string
	 */
	public function getActionPrefix() {
		return $this->sActionPrefix;
	}

	/**
	 * @param string $sPrefix
	 */
	public function setActionPrefix( $sPrefix ) :self {
		$this->sActionPrefix = rtrim( $sPrefix, '-' ).'-';
		return $this;
	}

	protected function flashNotice() {
		if ( !empty( $this->flash ) ) {
			echo sprintf( '<div class="updated icwp-admin-notice">%s</div>', esc_html( $this->flash ) );
		}
	}

	public function flushFlashMessage() {
		$cook = $this->getActionPrefix().'flash';
		$this->flash = $this->loadDP()->FetchCookie( $cook, '' );
		if ( !empty( $this->flash ) ) {
			$this->flash = sanitize_text_field( $this->flash );
		}
		$this->loadDP()->setDeleteCookie( $cook );
	}

	/**
	 * @deprecated 4.5
	 */
	protected function getNotices() :array {
		return [];
	}
}