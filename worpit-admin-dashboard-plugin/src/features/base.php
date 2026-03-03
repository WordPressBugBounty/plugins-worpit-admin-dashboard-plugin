<?php

abstract class ICWP_APP_FeatureHandler_Base extends ICWP_APP_Foundation {

	/**
	 * @var \ICWP_APP_OptionsVO
	 */
	protected $opts;

	/**
	 * @var bool
	 */
	protected $requirementsMet;

	/**
	 * @var string
	 */
	const CollateSeparator = '--SEP--';
	/**
	 * @var string
	 */
	const PluginVersionKey = 'current_plugin_version';

	/**
	 * @var bool
	 */
	protected $bPluginDeleting = false;

	/**
	 * @var string
	 */
	protected $sOptionsStoreKey;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $slug;

	/**
	 * @var \ICWP_APP_Processor_Base|mixed
	 */
	protected $processor;

	/**
	 * @var string
	 */
	protected static $sActivelyDisplayedModuleOptions = '';

	/**
	 * @param array $properties
	 * @throws \Exception
	 */
	public function __construct( $properties = [] ) {
		if ( isset( $properties[ 'storage_key' ] ) ) {
			$this->sOptionsStoreKey = $properties[ 'storage_key' ];
		}

		if ( isset( $properties[ 'slug' ] ) ) {
			$this->slug = $properties[ 'slug' ];
		}

		// before proceeding, we must now test the system meets the minimum requirements.
		if ( $this->getModuleMeetRequirements() ) {

			$nRunPriority = $properties[ 'load_priority' ] ?? 100;
			// Handle any upgrades as necessary (only go near this if it's the admin area)
			add_action( 'plugins_loaded', [ $this, 'onWpPluginsLoaded' ], $nRunPriority );
			add_action( self::con()->doPluginPrefix( 'form_submit' ), [ $this, 'handleFormSubmit' ] );
			add_filter( self::con()->doPluginPrefix( 'filter_plugin_submenu_items' ), [
				$this,
				'filter_addPluginSubMenuItem'
			] );
			add_filter( self::con()->doPluginPrefix( 'get_feature_summary_data' ), [
				$this,
				'filter_getFeatureSummaryData'
			] );
			add_action( self::con()->doPluginPrefix( 'plugin_shutdown' ), [ $this, 'action_doFeatureShutdown' ] );
			add_action( self::con()->doPluginPrefix( 'delete_plugin' ), [ $this, 'deletePluginOptions' ] );

			$this->doPostConstruction();
		}
	}

	protected function getModuleMeetRequirements() :bool {
		return $this->requirementsMet ?? $this->requirementsMet = $this->verifyModuleMeetRequirements();
	}

	protected function verifyModuleMeetRequirements() :bool {
		$met = true;

		$php = $this->opts()->getFeatureRequirement( 'php' );
		if ( !empty( $php ) ) {

			if ( !empty( $php[ 'version' ] ) ) {
				$met = $met && $this->loadDP()->getPhpVersionIsAtLeast( $php[ 'version' ] );
			}

			if ( !empty( $php[ 'functions' ] ) && \is_array( $php[ 'functions' ] ) ) {
				foreach ( $php[ 'functions' ] as $sFunction ) {
					$met = $met && function_exists( $sFunction );
				}
			}
			if ( !empty( $php[ 'constants' ] ) && is_array( $php[ 'constants' ] ) ) {
				foreach ( $php[ 'constants' ] as $sConstant ) {
					$met = $met && defined( $sConstant );
				}
			}
		}

		return $met;
	}

	protected function doPostConstruction() {
	}

	public function onWpPluginsLoaded() {
		if ( $this->getIsMainFeatureEnabled()
			 && $this->getProcessor() instanceof \ICWP_APP_Processor_Base ) {
			$this->getProcessor()->run();
		}
	}

	protected function getProcessorClassName() :string {
		return \ucwords( self::con()->getOptionStoragePrefix() ).'Processor_'.
			   \str_replace( ' ', '', \ucwords( \str_replace( '_', ' ', $this->getFeatureSlug() ) ) );
	}

	public function opts() :\ICWP_APP_OptionsVO {
		if ( !isset( $this->opts ) ) {
			$this->opts = new \ICWP_APP_OptionsVO( $this->getFeatureSlug() );
			$this->opts->setRebuildFromFile( self::con()->getIsRebuildOptionsFromFile() );
			$this->opts->setOptionsStorageKey( $this->getOptionsStorageKey() );
			$this->opts->setIfLoadOptionsFromStorage( !self::con()->getIsResetPlugin() );
		}
		return $this->opts;
	}

	public function getIsUpgrading() :bool {
		return $this->getVersion() != self::con()->getVersion();
	}

	/**
	 * Hooked to the plugin's main plugin_shutdown action
	 */
	public function action_doFeatureShutdown() {
		if ( !$this->getIsPluginDeleting() ) {
			$this->savePluginOptions();
		}
	}

	/**
	 * @return bool
	 */
	public function getIsPluginDeleting() {
		return $this->bPluginDeleting;
	}

	/**
	 * @return string
	 */
	protected function getOptionsStorageKey() {
		if ( !isset( $this->sOptionsStoreKey ) ) {
			// not ideal as it doesn't take into account custom storage keys as when passed into the constructor
			$this->sOptionsStoreKey = $this->opts()->getFeatureProperty( 'storage_key' );
		}

		return self::con()->doPluginPrefix( $this->sOptionsStoreKey, '_' ).'_options';
	}

	/**
	 * @return \ICWP_APP_Processor_Base
	 */
	public function getProcessor() {
		if ( !isset( $this->processor ) ) {
			include_once( self::con()
							  ->getPath_SourceFile( sprintf( 'processors%s%s.php', \DIRECTORY_SEPARATOR, $this->getFeatureSlug() ) ) );
			$class = $this->getProcessorClassName();
			if ( !\class_exists( $class, false ) ) {
				return null;
			}
			$this->processor = new $class( $this );
		}
		return $this->processor;
	}

	/**
	 * @param bool $bEnable
	 * @return bool
	 */
	public function setIsMainFeatureEnabled( $bEnable ) {
		return $this->setOpt( 'enable_'.$this->getFeatureSlug(), $bEnable ? 'Y' : 'N' );
	}

	/**
	 * @return mixed
	 */
	public function getIsMainFeatureEnabled() {
		if ( apply_filters( self::con()->doPluginPrefix( 'globally_disabled' ), false ) ) {
			return false;
		}
		return $this->getOptIs( 'enable_'.$this->getFeatureSlug(), 'Y' )
			   || $this->getOptIs( 'enable_'.$this->getFeatureSlug(), true, true )
			   || ( $this->opts()->getFeatureProperty( 'auto_enabled' ) === true );
	}

	protected function getMainFeatureName() :string {
		return $this->name ?? $this->name = (string)$this->opts()->getFeatureProperty( 'name' );
	}

	/**
	 * @return string
	 */
	public function getFeatureSlug() {
		return $this->slug ??= $this->opts()->getFeatureProperty( 'slug' );
	}

	/**
	 * @return int
	 */
	public function getPluginInstallationTime() {
		return $this->getOpt( 'installation_time', 0 );
	}

	/**
	 * @param array $aItems
	 * @return array
	 */
	public function filter_addPluginSubMenuItem( $aItems ) {
		$sMenuTitleName = $this->opts()->getFeatureProperty( 'menu_title' );
		if ( is_null( $sMenuTitleName ) ) {
			$sMenuTitleName = $this->getMainFeatureName();
		}
		if ( $this->getIfShowFeatureMenuItem() && !empty( $sMenuTitleName ) ) {

			$sHumanName = self::con()->getHumanName();

			$bMenuHighlighted = $this->opts()->getFeatureProperty( 'highlight_menu_item' );
			if ( $bMenuHighlighted ) {
				$sMenuTitleName = sprintf( '<span class="icwp_highlighted">%s</span>', $sMenuTitleName );
			}
			$sMenuPageTitle = $sMenuTitleName.' - '.$sHumanName;
			$aItems[ $sMenuPageTitle ] = [
				$sMenuTitleName,
				self::con()->doPluginPrefix( $this->getFeatureSlug() ),
				[ $this, 'displayFeatureConfigPage' ]
			];

			$aAdditionalItems = $this->opts()->getAdditionalMenuItems();
			if ( !empty( $aAdditionalItems ) && is_array( $aAdditionalItems ) ) {

				foreach ( $aAdditionalItems as $aMenuItem ) {

					if ( empty( $aMenuItem[ 'callback' ] ) || !method_exists( $this, $aMenuItem[ 'callback' ] ) ) {
						continue;
					}

					$sMenuPageTitle = $sHumanName.' - '.$aMenuItem[ 'title' ];
					$aItems[ $sMenuPageTitle ] = [
						$aMenuItem[ 'title' ],
						self::con()->doPluginPrefix( $aMenuItem[ 'slug' ] ),
						[ $this, $aMenuItem[ 'callback' ] ]
					];
				}
			}
		}
		return $aItems;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalMenuItem() {
		return [];
	}

	/**
	 * @param array $aSummaryData
	 * @return array
	 */
	public function filter_getFeatureSummaryData( $aSummaryData ) {
		if ( !$this->getIfShowFeatureMenuItem() ) {
			return $aSummaryData;
		}

		$sMenuTitle = $this->opts()->getFeatureProperty( 'menu_title' );
		$aSummaryData[] = [
			'enabled'    => $this->getIsMainFeatureEnabled(),
			'active'     => self::$sActivelyDisplayedModuleOptions == $this->getFeatureSlug(),
			'slug'       => $this->getFeatureSlug(),
			'name'       => $this->getMainFeatureName(),
			'menu_title' => empty( $sMenuTitle ) ? $this->getMainFeatureName() : $sMenuTitle,
			'href'       => network_admin_url( 'admin.php?page='.self::con()
																	 ->doPluginPrefix( $this->getFeatureSlug() ) )
		];

		return $aSummaryData;
	}

	/**
	 * @return bool
	 */
	public function getIfShowFeatureMenuItem() {
		return $this->opts()->getFeatureProperty( 'show_feature_menu_item' );
	}

	/**
	 * @param string $sDefinitionKey
	 * @return mixed|null
	 */
	public function getDefinition( $sDefinitionKey ) {
		return $this->opts()->getFeatureDefinition( $sDefinitionKey );
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		return $this->opts()->getOpt( $sOptionKey, $mDefault );
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mValueToTest
	 * @param bool   $strict
	 */
	public function getOptIs( $sOptionKey, $mValueToTest, $strict = false ) :bool {
		$mOptionValue = $this->opts()->getOpt( $sOptionKey );
		return $strict ? $mOptionValue === $mValueToTest : $mOptionValue == $mValueToTest;
	}

	/**
	 * Retrieves the full array of options->values
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->buildOptions();
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		$sVersion = $this->getOpt( self::PluginVersionKey );
		return empty( $sVersion ) ? self::con()->getVersion() : $sVersion;
	}

	/**
	 * Sets the value for the given option key
	 *
	 * @param string $sOptionKey
	 * @param mixed  $mValue
	 * @return bool
	 */
	public function setOpt( $sOptionKey, $mValue ) {
		return $this->opts()->setOpt( $sOptionKey, $mValue );
	}

	/**
	 * @param array $options
	 */
	public function setOptions( $options ) {
		foreach ( $options as $sKey => $mValue ) {
			$this->setOpt( $sKey, $mValue );
		}
	}

	/**
	 * @param       $success
	 * @param array $data
	 */
	protected function sendAjaxResponse( $success, $data = [] ) {
		$success ? wp_send_json_success( $data ) : wp_send_json_error( $data );
	}

	/**
	 * Saves the options to the WordPress Options store.
	 * It will also update the stored plugin options version.
	 *
	 * @return bool
	 */
	public function savePluginOptions() {
		$this->initialiseKeyVars();
		$this->updateOptionsVersion();
		$this->doPrePluginOptionsSave();
		return $this->opts()->doOptionsSave();
	}

	/**
	 * Will initiate the plugin options structure for use by the UI builder.
	 *
	 * It doesn't set any values, just populates the array created in buildOptions()
	 * with values stored.
	 *
	 * It has to handle the conversion of stored values to data to be displayed to the user.
	 */
	public function buildOptions() {

		$aOptions = $this->opts()->getLegacyOptionsConfigData();
		foreach ( $aOptions as $nSectionKey => $aOptionsSection ) {

			if ( empty( $aOptionsSection ) || !isset( $aOptionsSection[ 'section_options' ] ) ) {
				continue;
			}

			foreach ( $aOptionsSection[ 'section_options' ] as $nKey => $aOptionParams ) {

				$sOptionKey = $aOptionParams[ 'key' ];
				$sOptionDefault = $aOptionParams[ 'default' ];
				$sOptionType = $aOptionParams[ 'type' ];

				if ( $this->getOpt( $sOptionKey ) === false ) {
					$this->setOpt( $sOptionKey, $sOptionDefault );
				}
				$mCurrentOptionVal = $this->getOpt( $sOptionKey );

				if ( $sOptionType == 'password' && !empty( $mCurrentOptionVal ) ) {
					$mCurrentOptionVal = '';
				}
				elseif ( $sOptionType == 'array' ) {

					if ( empty( $mCurrentOptionVal ) ) {
						$mCurrentOptionVal = '';
					}
					else {
						$mCurrentOptionVal = implode( "\n", $mCurrentOptionVal );
					}
					$aOptionParams[ 'rows' ] = substr_count( $mCurrentOptionVal, "\n" ) + 1;
				}
				elseif ( $sOptionType == 'yubikey_unique_keys' ) {

					if ( empty( $mCurrentOptionVal ) ) {
						$mCurrentOptionVal = '';
					}
					else {
						$aDisplay = [];
						foreach ( $mCurrentOptionVal as $aParts ) {
							$aDisplay[] = key( $aParts ).', '.reset( $aParts );
						}
						$mCurrentOptionVal = implode( "\n", $aDisplay );
					}
					$aOptionParams[ 'rows' ] = substr_count( $mCurrentOptionVal, "\n" ) + 1;
				}

				if ( $sOptionType == 'text' ) {
					$mCurrentOptionVal = stripslashes( $mCurrentOptionVal );
				}
				$mCurrentOptionVal = is_scalar( $mCurrentOptionVal ) ? esc_attr( $mCurrentOptionVal ) : $mCurrentOptionVal;

				$aOptionParams[ 'value' ] = $mCurrentOptionVal;

				// Build strings
				$aParamsWithStrings = $this->loadStrings_Options( $aOptionParams );
				$aOptionsSection[ 'section_options' ][ $nKey ] = $aParamsWithStrings;
			}

			$aOptions[ $nSectionKey ] = $this->loadStrings_SectionTitles( $aOptionsSection );
		}

		return $aOptions;
	}

	/**
	 * @param $aOptionsParams
	 */
	protected function loadStrings_Options( $aOptionsParams ) {
		return $aOptionsParams;
	}

	/**
	 * @param $aOptionsParams
	 */
	protected function loadStrings_SectionTitles( $aOptionsParams ) {
		return $aOptionsParams;
	}

	/**
	 * Ensures that certain key options are always initialized.
	 */
	protected function initialiseKeyVars() {
	}

	/**
	 * This is the point where you would want to do any options verification
	 */
	protected function doPrePluginOptionsSave() {
	}

	protected function updateOptionsVersion() {
		if ( $this->getIsUpgrading() || self::con()->getIsRebuildOptionsFromFile() ) {
			$this->setOpt( self::PluginVersionKey, self::con()->getVersion() );
			$this->opts()->cleanTransientStorage();
		}
	}

	/**
	 * Deletes all the options including direct save.
	 */
	public function deletePluginOptions() {
		if ( apply_filters( self::con()->doPluginPrefix( 'has_permission_to_submit' ), true ) ) {
			$this->opts()->doOptionsDelete();
			$this->bPluginDeleting = true;
		}
	}

	/**
	 * @return string
	 */
	protected function collateAllFormInputsForAllOptions() {

		$aOptions = $this->buildOptions();

		$aToJoin = [];
		foreach ( $aOptions as $aOptionsSection ) {

			if ( empty( $aOptionsSection ) ) {
				continue;
			}
			foreach ( $aOptionsSection[ 'section_options' ] as $aOption ) {
				$aToJoin[] = $aOption[ 'type' ].':'.$aOption[ 'key' ];
			}
		}
		return implode( self::CollateSeparator, $aToJoin );
	}

	public function handleFormSubmit() :bool {
		if ( !$this->verifyFormSubmit() ) {
			return false;
		}
		$this->doSaveStandardOptions();
		$this->doExtraSubmitProcessing();
		return true;
	}

	protected function verifyFormSubmit() {
		if ( !apply_filters( self::con()->doPluginPrefix( 'has_permission_to_submit' ), true ) ) {
//				TODO: manage how we react to prohibited submissions
			return false;
		}

		// Now verify this is really a valid submission.
		return check_admin_referer( self::con()->getPluginPrefix() );
	}

	/**
	 * @return bool
	 */
	protected function doSaveStandardOptions() {
		$allOptions = $this->loadDP()->FetchPost( self::con()->doPluginPrefix( 'all_options_input', '_' ) );
		return empty( $allOptions ) ? true : $this->updatePluginOptionsFromSubmit( (string)$allOptions );
	}

	protected function doExtraSubmitProcessing() {
	}

	/**
	 * @param string $allOptionsInput - comma separated list of all the input keys to be processed from the $_POST
	 * @return void|bool
	 */
	protected function updatePluginOptionsFromSubmit( string $allOptionsInput ) {
		if ( empty( $allOptionsInput ) ) {
			return true;
		}
		$DP = $this->loadDP();
		foreach ( \explode( self::CollateSeparator, $allOptionsInput ) as $inputKey ) {
			list( $optType, $optKey ) = \explode( ':', $inputKey );

			$value = $DP->FetchPost( self::con()->doPluginPrefix( $optKey, '_' ) );
			if ( \is_null( $value ) ) {

				if ( $optType == 'text' || $optType == 'email' ) { //if it was a text box, and it's null, don't update anything
					continue;
				}
				elseif ( $optType == 'checkbox' ) { //if it was a checkbox, and it's null, it means 'N'
					$value = 'N';
				}
				elseif ( $optType == 'integer' ) { //if it was a integer, and it's null, it means '0'
					$value = 0;
				}
			}
			else { //handle any pre-processing we need to.

				if ( $optType == 'text' || $optType == 'email' ) {
					$value = trim( $value );
				}
				if ( $optType == 'integer' ) {
					$value = intval( $value );
				}
				elseif ( $optType == 'array' ) { //arrays are textareas, where each is separated by newline
					$value = array_filter( explode( "\n", $value ), 'trim' );
				}
				elseif ( $optType == 'email' && function_exists( 'is_email' ) && !is_email( $value ) ) {
					$value = '';
				}
				elseif ( $optType == 'multiple_select' ) {
				}
			}
			$this->setOpt( $optKey, $value );
		}
		return $this->savePluginOptions();
	}

	public function displayFeatureConfigPage() {
		$this->display();
	}

	protected function getBaseDisplayData() :array {
		$con = self::con();
		self::$sActivelyDisplayedModuleOptions = $this->getFeatureSlug();
		return [
			'var_prefix'      => $con->getOptionStoragePrefix(),
			'sPluginName'     => $con->getHumanName(),
			'sFeatureName'    => $this->getMainFeatureName(),
			'bFeatureEnabled' => $this->getIsMainFeatureEnabled(),
			'sTagline'        => $this->opts()->getFeatureTagline(),
			'fShowAds'        => $this->getIsShowMarketing(),
			'nonce_field'     => wp_create_nonce( $con->getPluginPrefix() ),
			'sFeatureSlug'    => self::con()->doPluginPrefix( $this->getFeatureSlug() ),
			'form_action'     => 'admin.php?page='.self::con()->doPluginPrefix( $this->getFeatureSlug() ),
			'nOptionsPerRow'  => 1,
			'aPluginLabels'   => $con->getPluginLabels(),

			'bShowStateSummary' => false,
			'aSummaryData'      => apply_filters( self::con()->doPluginPrefix( 'get_feature_summary_data' ), [] ),

			'aAllOptions'       => $this->buildOptions(),
			'aHiddenOptions'    => $this->opts()->getHiddenOptions(),
			'all_options_input' => $this->collateAllFormInputsForAllOptions(),

			'sPageTitle' => $this->getMainFeatureName(),
			'strings'    => [
				'go_to_settings'                    => __( 'Settings', 'worpit-admin-dashboard-plugin' ),
				'on'                                => __( 'On', 'worpit-admin-dashboard-plugin' ),
				'off'                               => __( 'Off', 'worpit-admin-dashboard-plugin' ),
				'more_info'                         => __( 'More Info', 'worpit-admin-dashboard-plugin' ),
				'blog'                              => __( 'Blog', 'worpit-admin-dashboard-plugin' ),
				'plugin_activated_features_summary' => __( 'Plugin Activated Features Summary:', 'worpit-admin-dashboard-plugin' ),
				'save_all_settings'                 => __( 'Save All Settings', 'worpit-admin-dashboard-plugin' ),
			],
		];
	}

	protected function getIsShowMarketing() :bool {
		return false;
	}

	/**
	 * @return void
	 */
	protected function display( array $data = [], string $view = '' ) {
		// Get Base Data
		$data[ 'mainFeatureInclude' ] = $this->loadDP()->addExtensionToFilePath( $view, 'php' );
		$this->displayTemplate(
			'index.php',
			\array_merge( $this->getBaseDisplayData(), $data )
		);
	}

	public function displayTemplate( string $template, array $data ) {
		if ( empty( $data[ 'unique_render_id' ] ) ) {
			$data[ 'unique_render_id' ] = 'u'.\substr( 0, 5, (string)wp_rand() );
		}
		$this->loadRenderer( self::con()->getPath_Templates() )
			 ->setTemplate( $template )
			 ->setRenderVars( $data )
			 ->display();
	}

	/**
	 * @return \ICWP_APP_OptionsVO
	 * @deprecated 4.5
	 */
	public function getOptionsVo() {
		return $this->opts();
	}
}