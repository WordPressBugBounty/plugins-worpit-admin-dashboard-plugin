<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Control;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

/**
 * Copyright (c) 2025 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * "iControlWP" is distributed under the GNU General Public License, Version 2,
 * June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class Controller extends \ICWP_APP_Foundation {

	/**
	 * @var \stdClass
	 */
	private static $conOpts;

	/**
	 * @var Controller
	 */
	public static $oInstance;

	/**
	 * @var string
	 */
	private static $sRootFile;

	/**
	 * @var bool
	 */
	protected $rebuildOpts;

	/**
	 * @var bool
	 */
	protected $reset;

	/**
	 * @var string
	 */
	private $pluginBaseFile;

	/**
	 * @var array
	 */
	private $requirementsMessages = [];

	/**
	 * @var string
	 */
	private $cfgHash;

	/**
	 * @var \ICWP_APP_FeatureHandler_Base[]
	 */
	public $modules = [];

	/**
	 * @return Controller
	 */
	public static function GetInstance( string $rootFile ) {
		if ( !isset( self::$oInstance ) ) {
			try {
				self::$oInstance = new self( $rootFile );
			}
			catch ( \Exception $e ) {
				return null;
			}
		}
		return self::$oInstance;
	}

	/**
	 * @throws \Exception
	 */
	private function __construct( string $rootFile ) {
		self::$sRootFile = $rootFile;
		$this->checkMinimumRequirements();
		add_action( 'plugins_loaded', [ $this, 'onWpPluginsLoaded' ], 0 );
	}

	/**
	 * @throws \Exception
	 */
	private function readPluginSpecification() :array {
		$spec = [];
		$contents = $this->loadDP()->readFileContentsUsingInclude( $this->getPathPluginSpec() );
		if ( !empty( $contents ) ) {
			$spec = \json_decode( $contents, true );
			if ( empty( $spec ) || !\is_array( $spec ) ) {
				throw new \Exception( 'Could not json_decode the plugin spec configuration.' );
			}
		}
		return $spec;
	}

	/**
	 * @throws \Exception
	 */
	private function checkMinimumRequirements() {
		if ( is_admin() ) {
			$meetsRequirements = true;
			$reqsMessages = $this->getRequirementsMessages();

			$minPHP = $this->getPluginSpec_Requirement( 'php' );
			if ( !empty( $minPHP ) ) {
				if ( \version_compare( \phpversion(), $minPHP, '<' ) ) {
					$reqsMessages[] = sprintf( 'PHP does not meet minimum version. Your version: %s.  Required Version: %s.', \PHP_VERSION, esc_html( $minPHP ) );
					$meetsRequirements = false;
				}
			}

			$minWP = $this->getPluginSpec_Requirement( 'wordpress' );
			if ( !empty( $minWP ) ) {
				$WPversion = $this->loadWP()->getWordpressVersion();
				if ( \version_compare( $WPversion, $minWP, '<' ) ) {
					$reqsMessages[] = sprintf( 'WordPress does not meet minimum version. Your version: %s.  Required Version: %s.', esc_html( $WPversion ), esc_html( $minWP ) );
					$meetsRequirements = false;
				}
			}

			if ( !$meetsRequirements ) {
				$this->requirementsMessages = $reqsMessages;
				add_action( 'admin_menu', [ $this, 'adminNoticeDoesNotMeetRequirements' ] );
				add_action( 'network_admin_notices', [ $this, 'adminNoticeDoesNotMeetRequirements' ] );
				throw new \Exception( 'Plugin does not meet minimum requirements' );
			}
		}
	}

	public function adminNoticeDoesNotMeetRequirements() {
		$msg = $this->getRequirementsMessages();
		if ( !empty( $msg ) ) {
			$this->loadRenderer( $this->getPath_Templates() )
				 ->setTemplate( 'notices/does-not-meet-requirements' )
				 ->setRenderVars( [
					 'strings' => [
						 'requirements'     => $msg,
						 'summary_title'    => sprintf( 'Web Hosting requirements for Plugin "%s" are not met and you should deactivate the plugin.', esc_html( $this->getHumanName() ) ),
						 'more_information' => 'Click here for more information on requirements'
					 ],
					 'hrefs'   => [
						 'more_information' => sprintf( 'https://wordpress.org/plugins/%s/faq', $this->getTextDomain() )
					 ]
				 ] )
				 ->display();
		}
	}

	protected function getRequirementsMessages() :array {
		return $this->requirementsMessages ?? $this->requirementsMessages = [];
	}

	/**
	 * Registers the plugins activation, deactivate and uninstall hooks.
	 */
	protected function registerActivationHooks() {
		register_deactivation_hook( $this->getRootFile(), [ $this, 'onWpDeactivatePlugin' ] );
	}

	public function onWpDeactivatePlugin() {
		$tmp = $this->getPath_PluginCache();
		if ( FileSystem::Instance()->isDir( $tmp ) ) {
			FileSystem::Instance()->deleteDir( $tmp );
		}

		if ( current_user_can( $this->getBasePermissions() ) && apply_filters( $this->doPluginPrefix( 'delete_on_deactivate' ), false ) ) {
			do_action( $this->doPluginPrefix( 'delete_plugin' ) );
			$this->deletePluginControllerOptions();
		}
	}

	public function onWpPluginsLoaded() {
		load_plugin_textdomain(
			$this->getTextDomain(),
			false,
			plugin_basename( $this->getPath_Languages() )
		);
		$this->doRegisterHooks();
	}

	protected function doRegisterHooks() {
		$this->registerActivationHooks();

		add_action( 'init', [ $this, 'onWpInit' ] );
		add_action( 'admin_init', [ $this, 'onWpAdminInit' ] );
		add_action( 'wp_loaded', [ $this, 'onWpLoaded' ] );

		add_action( 'admin_menu', [ $this, 'onWpAdminMenu' ] );
		add_action( 'network_admin_menu', [ $this, 'onWpAdminMenu' ] );

		add_filter( 'all_plugins', [ $this, 'doPluginLabels' ] );
		add_filter( 'plugin_action_links_'.$this->getPluginBaseFile(), [ $this, 'onWpPluginActionLinks' ], 50 );
		add_filter( 'plugin_row_meta', [ $this, 'onPluginRowMeta' ], 50, 2 );
		add_action( 'in_plugin_update_message-'.$this->getPluginBaseFile(), [ $this, 'onWpPluginUpdateMessage' ] );

		add_action( 'shutdown', [ $this, 'onWpShutdown' ] );

		// outsource the collection of admin notices
		if ( is_admin() ) {
			$this->loadAdminNoticesProcessor()->setActionPrefix( $this->doPluginPrefix() );
		}
	}

	public function onWpAdminInit() {
		add_action( 'admin_enqueue_scripts', [ $this, 'onWpEnqueueAdmin' ], 100 );
	}

	public function onWpEnqueueAdmin( $hook = '' ) {
		if ( \strpos( (string)$hook, 'icontrolwp_page_icwp' ) === 0 ) {
			$this->enqueueAdminCss();
			$this->enqueueAdminJs();
		}
	}

	public function onWpLoaded() {
		if ( $this->getIsValidAdminArea() ) {
			$this->doPluginFormSubmit();
		}
	}

	public function onWpInit() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendCss' ], 99 );
	}

	public function onWpAdminMenu() {
		if ( $this->getIsValidAdminArea() ) {
			$this->createPluginMenu();
		}
	}

	protected function createPluginMenu() {
		if ( !apply_filters( $this->doPluginPrefix( 'filter_hidePluginMenu' ), !$this->getPluginSpec_Menu( 'show' ) ) ) {
			if ( $this->getPluginSpec_Menu( 'top_level' ) ) {

				$labels = $this->getPluginLabels();

				$title = $this->getPluginSpec_Menu( 'title' );
				if ( \is_null( $title ) ) {
					$title = $this->getHumanName();
				}

				$icon = $this->getPluginUrl_Image( $this->getPluginSpec_Menu( 'icon_image' ) );
				$iconURL = empty( $labels[ 'icon_url_16x16' ] ) ? $icon : $labels[ 'icon_url_16x16' ];

				$fullMenuID = $this->getPluginPrefix();
				add_menu_page(
					$this->getHumanName(),
					$title,
					$this->getBasePermissions(),
					$fullMenuID,
					function () {
					},
					$iconURL
				);

				if ( $this->getPluginSpec_Menu( 'has_submenu' ) ) {

					$itesm = apply_filters( $this->doPluginPrefix( 'filter_plugin_submenu_items' ), [] );
					if ( !empty( $itesm ) ) {
						foreach ( $itesm as $title => $menuItem ) {
							list( $text, $itemID, $itemCallback ) = $menuItem;
							add_submenu_page(
								$fullMenuID,
								$title,
								$text,
								$this->getBasePermissions(),
								$itemID,
								$itemCallback
							);
						}
					}
				}

				if ( $this->getPluginSpec_Menu( 'do_submenu_fix' ) ) {
					$this->fixSubmenu();
				}
			}
		}
	}

	protected function fixSubmenu() {
		global $submenu;
		$sFullParentMenuId = $this->getPluginPrefix();
		if ( isset( $submenu[ $sFullParentMenuId ] ) ) {
			unset( $submenu[ $sFullParentMenuId ][ 0 ] );
		}
	}

	/**
	 * @param array|mixed  $meta
	 * @param string|mixed $pluginFile
	 * @return array
	 */
	public function onPluginRowMeta( $meta, $pluginFile ) :array {
		if ( $pluginFile === $this->getPluginBaseFile() ) {
			if ( !\is_array( $meta ) ) {
				$meta = [];
			}
			$template = '<strong><a href="%s" target="_blank">%s</a></strong>';
			foreach ( $this->getPluginSpec_PluginMeta() as $linkData ) {
				$link = sprintf( $template, $linkData[ 'href' ], $linkData[ 'name' ] );
				$meta[] = $link;
			}
		}
		return $meta;
	}

	/**
	 * @param array|mixed $actionLinks
	 */
	public function onWpPluginActionLinks( $actionLinks ) :array {
		if ( $this->getIsValidAdminArea() ) {
			if ( !\is_array( $actionLinks ) ) {
				$actionLinks = [];
			}
			$template = '<a href="%s" target="%s">%s</a>';
			foreach ( $this->conOpts()->plugin_spec[ 'action_links' ][ 'add' ] ?? [] as $link ) {
				if ( empty( $link[ 'name' ] ) || ( empty( $link[ 'url_method_name' ] ) && empty( $link[ 'href' ] ) ) ) {
					continue;
				}

				if ( !empty( $link[ 'url_method_name' ] ) ) {
					$method = $link[ 'url_method_name' ];
					if ( \method_exists( $this, $method ) ) {
						$settingsLink = sprintf( $template, $this->{$method}(), "_top", $link[ 'name' ] );
						\array_unshift( $actionLinks, $settingsLink );
					}
				}
				elseif ( !empty( $link[ 'href' ] ) ) {
					$settingsLink = sprintf( $template, $link[ 'href' ], "_blank", $link[ 'name' ] );
					\array_unshift( $actionLinks, $settingsLink );
				}
			}
		}
		return $actionLinks;
	}

	public function enqueueFrontendCss() {
		foreach ( $this->getPluginSpec_Include( 'frontend' )[ 'css' ] ?? [] as $asset ) {
			$uniq = $this->doPluginPrefix( $asset );
			wp_register_style( $uniq, $this->getPluginUrl_Css( $asset.'.css' ), ( empty( $dependent ) ? false : $dependent ), $this->getVersion() );
			wp_enqueue_style( $uniq );
			$dependent = $uniq;
		}
	}

	public function enqueueAdminJs() {
		$includeTypes = \array_filter( [
			'admin'        => $this->getIsValidAdminArea(),
			'plugin_admin' => $this->getIsPage_PluginAdmin(),
		] );

		foreach ( \array_keys( $includeTypes ) as $type ) {
			$dependent = false;
			foreach ( $this->getPluginSpec_Include( $type )[ 'js' ] ?? [] as $asset ) {
				$url = $this->getPluginUrl_Js( $asset.'.js' );
				if ( !empty( $url ) ) {
					$uniq = $this->doPluginPrefix( $asset );
					wp_register_script( $uniq, $url, $dependent, $this->getVersion().wp_rand(), [ 'in_footer' => true ] );
					wp_enqueue_script( $uniq );
					$dependent = $uniq;
				}
			}
		}
	}

	public function enqueueAdminCss() {
		$includeTypes = \array_filter( [
			'admin'        => $this->getIsValidAdminArea(),
			'plugin_admin' => $this->getIsPage_PluginAdmin(),
		] );

		foreach ( \array_keys( $includeTypes ) as $type ) {
			$dependent = false;
			foreach ( $this->getPluginSpec_Include( $type )[ 'css' ] ?? [] as $asset ) {
				$url = $this->getPluginUrl_Css( $asset.'.css' );
				if ( !empty( $url ) ) {
					$sUnique = $this->doPluginPrefix( $asset );
					wp_register_style( $sUnique, $url, $dependent, $this->getVersion().wp_rand() );
					wp_enqueue_style( $sUnique );
					$dependent = $sUnique;
				}
			}
		}
	}

	/**
	 * Displays a message in the plugins listing when a plugin has an update available.
	 */
	public function onWpPluginUpdateMessage() {
		$default = sprintf( 'Upgrade Now To Get The Latest Available %s Features.', $this->getHumanName() );
		$msg = apply_filters( $this->doPluginPrefix( 'plugin_update_message' ), $default );
		echo empty( $msg ) ? '' :
			sprintf( '<div class="%s plugin_update_message">%s</div>', esc_attr( $this->getPluginPrefix() ), esc_html( $msg ) );
	}

	/**
	 * @param array|mixed $plugins
	 */
	public function doPluginLabels( $plugins ) :array {
		if ( !\is_array( $plugins ) ) {
			$plugins = [];
		}
		$file = $this->getPluginBaseFile();
		if ( \is_array( $plugins[ $file ] ?? null ) ) {
			$plugins[ $file ] = \array_merge( $plugins[ $file ], $this->getPluginLabels() );
		}
		return $plugins;
	}

	public function getPluginLabels() :array {
		$labels = apply_filters( $this->doPluginPrefix( 'plugin_labels' ), $this->getPluginSpec_Labels() );
		return \is_array( $labels ) ? $labels : [];
	}

	public function onWpShutdown() {
		do_action( $this->doPluginPrefix( 'pre_plugin_shutdown' ) );
		do_action( $this->doPluginPrefix( 'plugin_shutdown' ) );
		$this->saveCurrentPluginControllerOptions();
		$this->deleteFlags();
	}

	protected function deleteFlags() {
		$FS = $this->loadFS();
		if ( $FS->exists( $this->getPath_Flags( 'rebuild' ) ) ) {
			$FS->deleteFile( $this->getPath_Flags( 'rebuild' ) );
		}
		if ( $this->getIsResetPlugin() ) {
			$FS->deleteFile( $this->getPath_Flags( 'reset' ) );
		}
	}

	protected function doPluginFormSubmit() {
		if ( $this->getIsPluginFormSubmit() ) {
			// do all the plugin feature/options saving
			do_action( $this->doPluginPrefix( 'form_submit' ) );
			if ( $this->getIsPage_PluginAdmin() ) {
				$this->loadWP()->doRedirect( $this->loadWP()->getUrl_CurrentAdminPage() );
			}
		}
	}

	/**
	 * @param string $suffix
	 * @param string $glue
	 */
	public function doPluginPrefix( $suffix = '', $glue = '-' ) :string {
		$prefix = $this->getPluginPrefix( $glue );

		if ( $suffix == $prefix || \strpos( $suffix, $prefix.$glue ) === 0 ) { //it already has the full prefix
			return $suffix;
		}

		return sprintf( '%s%s%s', $prefix, empty( $suffix ) ? '' : $glue, empty( $suffix ) ? '' : $suffix );
	}

	public function doPluginOptionPrefix( string $toPrefix ) :string {
		return $this->doPluginPrefix( $toPrefix, '_' );
	}

	/**
	 * @return mixed|null
	 */
	protected function getPluginSpec_Include( string $type ) {
		return $this->conOpts()->plugin_spec[ 'includes' ][ $type ] ?? null;
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	protected function getPluginSpec_Labels( $key = '' ) {
		$labels = $this->conOpts()->plugin_spec[ 'labels' ] ?? [];
		//Prep the icon urls
		if ( !empty( $labels[ 'icon_url_16x16' ] ) ) {
			$labels[ 'icon_url_16x16' ] = $this->getPluginUrl_Image( $labels[ 'icon_url_16x16' ] );
		}
		if ( !empty( $labels[ 'icon_url_32x32' ] ) ) {
			$labels[ 'icon_url_32x32' ] = $this->getPluginUrl_Image( $labels[ 'icon_url_32x32' ] );
		}

		if ( empty( $key ) ) {
			return $labels;
		}

		return $this->conOpts()->plugin_spec[ 'labels' ][ $key ] ?? null;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	protected function getPluginSpec_Menu( $key ) {
		return $this->conOpts()->plugin_spec[ 'menu' ][ $key ] ?? null;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getPluginSpec_Path( $key ) {
		return $this->conOpts()->plugin_spec[ 'paths' ][ $key ] ?? null;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	protected function getPluginSpec_Property( $key ) {
		return $this->conOpts()->plugin_spec[ 'properties' ][ $key ] ?? null;
	}

	/**
	 * @return array
	 */
	protected function getPluginSpec_PluginMeta() {
		$conOpts = $this->conOpts();
		return ( isset( $conOpts->plugin_spec[ 'plugin_meta' ] ) && \is_array( $conOpts->plugin_spec[ 'plugin_meta' ] ) ) ? $conOpts->plugin_spec[ 'plugin_meta' ] : [];
	}

	/**
	 * @param string $sKey
	 * @return mixed|null
	 */
	protected function getPluginSpec_Requirement( $sKey ) {
		return $this->conOpts()->plugin_spec[ 'requirements' ][ $sKey ] ?? null;
	}

	/**
	 * @return string
	 */
	public function getBasePermissions() {
		return $this->getPluginSpec_Property( 'base_permissions' );
	}

	/**
	 * @param bool $checkUserPermissions
	 * @return bool
	 */
	public function getIsValidAdminArea( $checkUserPermissions = true ) :bool {
		if ( $checkUserPermissions && did_action( 'init' ) && !current_user_can( $this->getBasePermissions() ) ) {
			return false;
		}

		$WP = $this->loadWP();
		if ( !$WP->isMultisite() && is_admin() ) {
			return true;
		}
		elseif ( $WP->isMultisite() && $this->getIsWpmsNetworkAdminOnly() && is_network_admin() ) {
			return true;
		}
		return false;
	}

	public function getOptionStoragePrefix() :string {
		return $this->getPluginPrefix( '_' ).'_';
	}

	/**
	 * @param $glue
	 */
	public function getPluginPrefix( $glue = '-' ) :string {
		return sprintf( '%s%s%s', $this->getPluginSpec_Property( 'slug_parent' ), $glue, $this->getPluginSlug() );
	}

	/**
	 * Default is to take the 'Name' from the labels section but can override with "human_name" from property section.
	 * @return string
	 */
	public function getHumanName() {
		$labels = $this->getPluginLabels();
		return empty( $labels[ 'Name' ] ) ? $this->getPluginSpec_Property( 'human_name' ) : $labels[ 'Name' ];
	}

	public function getIsPage_PluginAdmin() :bool {
		return \strpos( $this->loadWP()->getCurrentWpAdminPage(), $this->getPluginPrefix() ) === 0;
	}

	/**
	 * @return bool
	 */
	protected function getIsPluginFormSubmit() {
		$isSubmit = false;
		foreach (
			[
				$this->doPluginOptionPrefix( 'plugin_form_submit' ),
				'icwp_link_action'
			] as $option
		) {
			if ( !\is_null( $this->loadDP()->FetchRequest( $option ) ) ) {
				$isSubmit = true;
				break;
			}
		}
		return $isSubmit;
	}

	public function getIsRebuildOptionsFromFile() :bool {
		if ( isset( $this->rebuildOpts ) ) {
			return $this->rebuildOpts;
		}

		// The first choice is to look for the file hash. If it's "always" empty, it means we could never
		// hash the file in the first place so it's not ever effectively used and it falls back to the rebuild file
		$conOptions = $this->conOpts();
		$specPath = $this->getPathPluginSpec();
		$currentHash = @\md5_file( $specPath );
		$modTime = $this->loadFS()->getModifiedTime( $specPath );

		$this->rebuildOpts = true;

		if ( isset( $conOptions->hash ) && is_string( $conOptions->hash ) && ( $conOptions->hash == $currentHash ) ) {
			$this->rebuildOpts = false;
		}
		elseif ( isset( $conOptions->mod_time ) && ( $modTime < $conOptions->mod_time ) ) {
			$this->rebuildOpts = false;
		}

		$conOptions->hash = $currentHash;
		$conOptions->mod_time = $modTime;
		return $this->rebuildOpts;
	}

	public function getIsResetPlugin() :bool {
		return $this->reset ?? $this->reset = (bool)$this->loadFS()->isFile( $this->getPath_Flags( 'reset' ) );
	}

	/**
	 * @return bool
	 */
	public function getIsWpmsNetworkAdminOnly() {
		return $this->getPluginSpec_Property( 'wpms_network_admin_only' );
	}

	/**
	 * This is the path to the main plugin file relative to the WordPress plugins directory.
	 */
	public function getPluginBaseFile() :string {
		return $this->pluginBaseFile ?? $this->pluginBaseFile = plugin_basename( $this->getRootFile() );
	}

	public function getPluginSlug() {
		return $this->getPluginSpec_Property( 'slug_plugin' );
	}

	public function getPluginUrl( string $path = '' ) :string {
		return add_query_arg( [ 'ver' => $this->getVersion() ], plugins_url( '/', $this->getRootFile() ).$path );
	}

	public function getPluginUrl_Asset( string $asset ) :string {
		return $this->loadFS()->exists( $this->getPath_Assets( $asset ) ) ?
			$this->getPluginUrl( $this->getPluginSpec_Path( 'assets' ).'/'.$asset ) : '';
	}

	public function getPluginUrl_Css( string $asset ) :string {
		return $this->getPluginUrl_Asset( 'css/'.$asset );
	}

	public function getPluginUrl_Image( string $asset ) :string {
		return $this->getPluginUrl_Asset( 'images/'.$asset );
	}

	public function getPluginUrl_Js( string $asset ) :string {
		return $this->getPluginUrl_Asset( 'js/'.$asset );
	}

	public function getPath_Assets( string $asset = '' ) :string {
		return trailingslashit( path_join( $this->getRootDir(), $this->getPluginSpec_Path( 'assets' ) ) ).$asset;
	}

	/**
	 * @param string $flag
	 * @return string
	 */
	public function getPath_Flags( $flag = '' ) :string {
		return path_join( $this->getRootDir().$this->getPluginSpec_Path( 'flags' ), $flag );
	}

	/**
	 * @param string $file
	 * @return string|null
	 */
	public function getPath_Temp( $file = '' ) {
		$path = null;
		$tmpDir = path_join( $this->getRootDir(), $this->getPluginSpec_Path( 'temp' ) );
		if ( $this->loadFS()->mkdir( $tmpDir ) ) {
			if ( empty( $file ) ) {
				$path = trailingslashit( $tmpDir );
			}
			else {
				$path = path_join( $tmpDir, $file );
			}
		}
		return $path;
	}

	public function getPath_Languages() :string {
		return trailingslashit( path_join( $this->getRootDir(), $this->getPluginSpec_Path( 'languages' ) ) );
	}

	public function getPath_PluginCache() :string {
		return path_join( WP_CONTENT_DIR, $this->getPluginSpec_Path( 'cache' ) );
	}

	/**
	 * get the root directory for the plugin source with the trailing slash
	 */
	public function getPath_Source() :string {
		return $this->getPath_SourceCurrent();
	}

	/**
	 * get the root directory for the plugin with the trailing slash
	 */
	public function getPath_SourceCurrent() :string {
		return trailingslashit( path_join( $this->getRootDir(), $this->getPluginSpec_Path( 'source' ) ) );
	}

	/**
	 * Get the directory for the plugin source files with the trailing slash
	 * @param string $file
	 * @return string
	 */
	public function getPath_SourceFile( $file = '' ) :string {
		return $this->getPath_Source().$file;
	}

	public function getPath_Templates() :string {
		return trailingslashit( path_join( $this->getRootDir(), $this->getPluginSpec_Path( 'templates' ) ) );
	}

	private function getPathPluginSpec() :string {
		return path_join( $this->getRootDir(), 'plugin-spec.php' );
	}

	/**
	 * Get the root directory for the plugin with the trailing slash
	 */
	public function getRootDir() :string {
		return trailingslashit( \dirname( $this->getRootFile() ) );
	}

	public function getRootFile() :string {
		if ( !isset( self::$sRootFile ) ) {
			self::$sRootFile = __FILE__;
		}
		return self::$sRootFile;
	}

	/**
	 * @return string
	 */
	public function getTextDomain() {
		return $this->getPluginSpec_Property( 'text_domain' );
	}

	public function getVersion() :string {
		return (string)$this->getPluginSpec_Property( 'version' );
	}

	protected function conOpts() :\stdClass {
		if ( !isset( self::$conOpts ) ) {

			self::$conOpts = $this->loadWP()->getOption( $this->getPluginControllerOptionsKey() );
			if ( !is_object( self::$conOpts ) ) {
				self::$conOpts = new \stdClass();
			}

			// Used at the time of saving during WP Shutdown to determine whether saving is necessary. TODO: Extend to plugin options
			if ( empty( $this->cfgHash ) ) {
				$this->cfgHash = \md5( \serialize( self::$conOpts ) );
			}

			if ( $this->getIsRebuildOptionsFromFile() ) {
				self::$conOpts->plugin_spec = $this->readPluginSpecification();
			}
		}
		return self::$conOpts;
	}

	protected function deletePluginControllerOptions() {
		$this->setPluginControllerOptions( false );
		$this->saveCurrentPluginControllerOptions();
	}

	protected function saveCurrentPluginControllerOptions() {
		$options = $this->conOpts();
		if ( $this->cfgHash != \md5( \serialize( $options ) ) ) {
			add_filter( $this->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
			$this->loadWP()->updateOption( $this->getPluginControllerOptionsKey(), $options );
			remove_filter( $this->doPluginPrefix( 'bypass_permission_to_manage' ), '__return_true' );
		}
	}

	/**
	 * This should always be used to modify or delete the options as it works within the Admin Access Permission system.
	 * @param \stdClass|bool $oOptions
	 * @return $this
	 */
	protected function setPluginControllerOptions( $oOptions ) :self {
		self::$conOpts = $oOptions;
		return $this;
	}

	private function getPluginControllerOptionsKey() :string {
		return \strtolower( \get_class( $this ) );
	}

	/**
	 * @return \ICWP_APP_FeatureHandler_Plugin
	 */
	public function loadCorePluginFeatureHandler() {
		return $this->loadFeatureHandler( [
			'slug'          => 'plugin',
			'load_priority' => 10
		] );
	}

	public function loadAllFeatures() :bool {
		foreach ( $this->loadCorePluginFeatureHandler()->getActivePluginFeatures() as $modProperties ) {
			$this->loadFeatureHandler( $modProperties );
		}
		return true;
	}

	/**
	 * @return \ICWP_APP_FeatureHandler_Base|mixed
	 */
	public function loadFeatureHandler( array $properties ) {
		$slug = $properties[ 'slug' ];
		if ( !isset( $this->modules[ $slug ] ) ) {
			$className = $this->modMap()[ $slug ];
			$this->modules[ $slug ] = new $className( $properties );
		}
		return $this->modules[ $slug ];
	}

	private function modMap() :array {
		return [
			'plugin'           => \ICWP_APP_FeatureHandler_Plugin::class,
			'autoupdates'      => \ICWP_APP_FeatureHandler_Autoupdates::class,
			'compatibility'    => \ICWP_APP_FeatureHandler_Compatibility::class,
			'security'         => \ICWP_APP_FeatureHandler_Security::class,
			'google_analytics' => \ICWP_APP_FeatureHandler_GoogleAnalytics::class,
			'whitelabel'       => \ICWP_APP_FeatureHandler_Whitelabel::class,
		];
	}

	/**
	 * @deprecated 4.5
	 */
	public function filter_hidePluginFromTableList( $plugins ) {
		return $plugins;
	}

	/**
	 * @deprecated 4.5 - no longer autoupdate from within the plugin
	 */
	public function setUpdateFirstDetectedAt( $updateData ) {
		return $updateData;
	}

	/**
	 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
	 * based on the plugin settings.
	 * @param bool          $doUpdate
	 * @param string|object $mItem
	 * @return bool
	 * @deprecated 4.5 - will push autoupdates from App
	 */
	public function onWpAutoUpdate( $doUpdate, $mItem ) {
		return $doUpdate;
	}

	/**
	 * @deprecated 4.5.1
	 */
	protected function doLoadTextDomain() {
	}

	/**
	 * @deprecated 4.5.1
	 */
	protected function getPluginSpec_ActionLinks( string $key ) :array {
		return $this->conOpts()->plugin_spec[ 'action_links' ][ $key ] ?? [];
	}
}