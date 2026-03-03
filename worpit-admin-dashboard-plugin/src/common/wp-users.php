<?php

class ICWP_APP_WpUsers extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpUsers
	 */
	protected static $I = null;

	private function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ??= new self();
	}

	/**
	 * If setting password, do not send the hashed password as this will hash it for you
	 *
	 * @return int|\WP_Error
	 */
	public function createUser( array $newData, bool $sendUserNotification = false ) {
		//set defaults for unset vars
		$new = wp_parse_args( $newData, [
			'user_registered' => strftime( '%F %T', time() ),
			'display_name'    => false,
			'user_url'        => '',
			'description'     => ''
		] );
		if ( !empty( $new[ 'user_pass' ] ) ) {
			$new[ 'user_pass' ] = wp_hash_password( $new[ 'user_pass' ] );
		}

		$newUserId = wp_insert_user( $new );
		if (!is_wp_error( $newUserId ) && \function_exists( 'wp_new_user_notification' ) ) {
			wp_new_user_notification( $newUserId, null, $sendUserNotification ? 'both' : 'admin' );
		}
		return $newUserId;
	}

	/**
	 * @throws \Exception
	 */
	public function deleteUser( int $userID, bool $permitAdminDelete = false, int $reassignUserID = 0 ) :bool {
		if ( !\function_exists( 'wp_delete_user' ) ) {
			include( ABSPATH.'wp-admin/includes/user.php' );
			if ( !function_exists( 'wp_delete_user' ) ) {
				throw new Exception( 'Could not find the function wp_delete_user()' );
			}
		}
		if ( empty( $userID ) ) {
			throw new Exception( 'User ID value was not set' );
		}
		if ( $userID <= 0 ) {
			throw new Exception( sprintf( 'Supplied User ID "%s" to delete was less than or equal to zero', esc_html( $userID ) ) );
		}

		$user = $this->getUserById( $userID );
		if ( empty( $user ) ) {
			throw new Exception( sprintf( 'Could not load User with ID "%s" to delete', esc_html( $userID ) ) );
		}
		if ( !$permitAdminDelete && $this->isUserAdmin( $user ) ) {
			throw new Exception( sprintf( 'Attempting to delete Administrator User ID "%s"', esc_html( $userID ) ) );
		}

		return wp_delete_user( $userID, empty( $reassignUserID ) ? null : $reassignUserID );
	}

	public function getCurrentUserLevel() :int {
		return $this->getCurrentWpUser() instanceof WP_User ? (int)$this->getCurrentWpUser()->get( 'user_level' ) : -1;
	}

	public function getCanAddUpdateCurrentUserMeta() :bool {
		$canMeta = false;
		try {
			if ( $this->isUserLoggedIn() ) {
				$key = 'icwp-flag-can-store-user-meta';
				$theMeta = $this->getUserMeta( $key );
				$canMeta = $theMeta == 'icwp' || $this->updateUserMeta( $key, 'icwp' );
			}
		}
		catch ( Exception $e ) {
		}
		return $canMeta;
	}

	/**
	 * @return null|WP_User
	 */
	public function getCurrentWpUser() {
		if ( $this->isUserLoggedIn() ) {
			if ( wp_get_current_user() instanceof WP_User ) {
				return wp_get_current_user();
			}
		}
		return null;
	}

	/**
	 * @return int - 0 if not logged in or can't get the current User
	 */
	public function getCurrentWpUserId() :int {
		$u = $this->getCurrentWpUser();
		return \is_null( $u ) ? 0 : (int)$u->ID;
	}

	/**
	 * @param $username
	 * @return ?\WP_User
	 */
	public function getUserByUsername( $username ) {
		return empty( $username ) ? null : get_user_by( 'login', $username );
	}

	/**
	 * @param int $nId
	 * @return WP_User|null
	 */
	public function getUserById( $nId ) {
		if ( version_compare( $this->loadWP()
								   ->getWordpressVersion(), '2.8.0', '<' ) || !function_exists( 'get_user_by' ) ) {
			return null;
		}
		return get_user_by( 'id', $nId );
	}

	/**
	 * @param string   $sKey   should be already prefixed
	 * @param int|null $userID - if omitted get for current user
	 * @return false|string
	 */
	public function getUserMeta( $sKey, $userID = null ) {
		if ( empty( $userID ) ) {
			$userID = $this->getCurrentWpUserId();
		}

		$mResult = false;
		if ( $userID > 0 ) {
			$mResult = get_user_meta( $userID, $sKey, true );
		}
		return $mResult;
	}

	/**
	 * @param \WP_User|null $user
	 */
	public function isUserAdmin( $user = null ) :bool {
		return $user instanceof \WP_User ? user_can( $user, 'manage_options' ) : $this->isUserLoggedIn() && current_user_can( 'manage_options' );
	}

	public function isUserLoggedIn() :bool {
		return did_action( 'init' ) && is_user_logged_in();
	}

	/**
	 * @param string $sRedirectUrl
	 */
	public function logoutUser( $sRedirectUrl = '' ) {
		empty( $sRedirectUrl ) ? wp_logout() : wp_logout_url( $sRedirectUrl );
	}

	/**
	 * Updates the user meta data for the current (or supplied user ID)
	 *
	 * @param string $sKey
	 * @param mixed  $mValue
	 * @param int    $nUserId -user ID
	 * @return bool
	 */
	public function updateUserMeta( $sKey, $mValue, $nUserId = null ) {
		if ( empty( $nUserId ) ) {
			$nUserId = $this->getCurrentWpUserId();
		}

		$bSuccess = false;
		if ( $nUserId > 0 ) {
			$bSuccess = update_user_meta( $nUserId, $sKey, $mValue );
		}
		return $bSuccess;
	}

	/**
	 * @param string $username
	 */
	public function setUserLoggedIn( $username, bool $silent = false ) :bool {
		$success = false;
		$user = $this->getUserByUsername( $username );
		if ( \is_a( $user, 'WP_User' ) ) {
			if ( !\defined( 'COOKIEHASH' ) ) {
				wp_cookie_constants();
			}
			wp_clear_auth_cookie();
			wp_set_current_user( $user->ID, $user->get( 'user_login' ) );
			wp_set_auth_cookie( $user->ID, true );
			if ( !$silent ) {
				do_action( 'wp_login', $user->get( 'user_login' ), $user );
			}
			$success = true;
		}
		return $success;
	}
}