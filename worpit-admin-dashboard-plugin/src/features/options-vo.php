<?php

class ICWP_APP_OptionsVO extends ICWP_APP_Foundation {

	/**
	 * @var array
	 */
	protected $aOptionsValues;

	/**
	 * @var array
	 */
	protected $aChangedOptionsTracker;

	/**
	 * @var array
	 */
	protected $aRawOptionsConfigData;

	/**
	 * @var bool
	 */
	protected $bNeedSave;

	/**
	 * @var bool
	 */
	protected $bRebuildFromFile = false;

	/**
	 * @var string
	 */
	protected $aOptionsKeys;

	/**
	 * @var string
	 */
	protected $sOptionsStorageKey;

	/**
	 *  by default we load from saved
	 * @var string
	 */
	protected $bLoadFromSaved = true;

	/**
	 * @var string
	 */
	protected $sOptionsName;

	/**
	 * @param string $sOptionsName
	 */
	public function __construct( $sOptionsName ) {
		$this->sOptionsName = $sOptionsName;
	}

	/**
	 * @return bool
	 */
	public function cleanTransientStorage() {
		return $this->loadWP()->deleteTransient( $this->getConfigStorageKey() );
	}

	/**
	 * @return bool
	 */
	public function doOptionsSave() {
		if ( !$this->getNeedSave() ) {
			return true;
		}
		$this->cleanOptions();
		$this->setNeedSave( false );
		return $this->loadWP()
					->updateOption( $this->getOptionsStorageKey(), $this->getAllOptionsValues() );
	}

	/**
	 * @return bool
	 */
	public function doOptionsDelete() {
		$oWp = $this->loadWP();
		$oWp->deleteTransient( $this->getConfigStorageKey() );
		return $oWp->deleteOption( $this->getOptionsStorageKey() );
	}

	/**
	 * @return array
	 */
	public function getAllOptionsValues() {
		return $this->loadOptionsValuesFromStorage();
	}

	/**
	 * Returns an array of all the transferable options and their values
	 * @return array
	 */
	public function getTransferableOptions() {

		$aOptions = $this->getAllOptionsValues();
		$aRawOptions = $this->getRawData_AllOptions();
		$aTransferable = [];
		foreach ( $aRawOptions as $nKey => $aOptionData ) {
			if ( isset( $aOptionData[ 'transferable' ] ) && $aOptionData[ 'transferable' ] === true ) {
				$aTransferable[ $aOptionData[ 'key' ] ] = $aOptions[ $aOptionData[ 'key' ] ];
			}
		}
		return $aTransferable;
	}

	/**
	 * @param $sProperty
	 * @return null|mixed
	 */
	public function getFeatureProperty( $sProperty ) {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'properties' ] ) && isset( $aRawConfig[ 'properties' ][ $sProperty ] ) ) ? $aRawConfig[ 'properties' ][ $sProperty ] : null;
	}

	/**
	 * @param string
	 * @return null|array
	 */
	public function getFeatureDefinition( $sDefinition ) {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'definitions' ] ) && isset( $aRawConfig[ 'definitions' ][ $sDefinition ] ) ) ? $aRawConfig[ 'definitions' ][ $sDefinition ] : null;
	}

	/**
	 * @param string $sReq
	 * @return null|mixed
	 */
	public function getFeatureRequirement( $sReq ) {
		$aReqs = $this->getRawData_Requirements();
		return ( is_array( $aReqs ) && isset( $aReqs[ $sReq ] ) ) ? $aReqs[ $sReq ] : null;
	}

	/**
	 * @deprecated 4.5
	 */
	public function getAdminNotices() {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'admin_notices' ] ) && is_array( $aRawConfig[ 'admin_notices' ] ) ) ? $aRawConfig[ 'admin_notices' ] : [];
	}

	/**
	 * @return string
	 */
	public function getFeatureTagline() {
		return $this->getFeatureProperty( 'tagline' );
	}

	/**
	 * Determines whether the given option key is a valid option
	 * @param string
	 * @return bool
	 */
	public function getIsValidOptionKey( $sOptionKey ) {
		return in_array( $sOptionKey, $this->getOptionsKeys() );
	}

	/**
	 * @return array
	 */
	public function getHiddenOptions() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aOptionsData = [];

		foreach ( $aRawData[ 'sections' ] as $nPosition => $aRawSection ) {

			// if hidden isn't specified we skip
			if ( !isset( $aRawSection[ 'hidden' ] ) || !$aRawSection[ 'hidden' ] ) {
				continue;
			}
			foreach ( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption[ 'section' ] != $aRawSection[ 'slug' ] ) {
					continue;
				}
				$aOptionsData[ $aRawOption[ 'key' ] ] = $this->getOpt( $aRawOption[ 'key' ] );
			}
		}
		return $aOptionsData;
	}

	/**
	 * @return array
	 */
	public function getLegacyOptionsConfigData() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aLegacyData = [];

		foreach ( $aRawData[ 'sections' ] as $nPosition => $aRawSection ) {

			if ( isset( $aRawSection[ 'hidden' ] ) && $aRawSection[ 'hidden' ] ) {
				continue;
			}

			$aLegacySection = [];
			$aLegacySection[ 'section_primary' ] = isset( $aRawSection[ 'primary' ] ) && $aRawSection[ 'primary' ];
			$aLegacySection[ 'section_slug' ] = $aRawSection[ 'slug' ];
			$aLegacySection[ 'section_options' ] = [];
			foreach ( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption[ 'section' ] != $aRawSection[ 'slug' ] ) {
					continue;
				}

				if ( isset( $aRawOption[ 'hidden' ] ) && $aRawOption[ 'hidden' ] ) {
					continue;
				}

				$aLegacyRawOption = [];
				$aLegacyRawOption[ 'key' ] = $aRawOption[ 'key' ];
				$aLegacyRawOption[ 'value' ] = ''; //value
				$aLegacyRawOption[ 'default' ] = $aRawOption[ 'default' ];
				$aLegacyRawOption[ 'type' ] = $aRawOption[ 'type' ];

				$aLegacyRawOption[ 'value_options' ] = [];
				if ( in_array( $aLegacyRawOption[ 'type' ], [ 'select', 'multiple_select' ] ) ) {
					foreach ( $aRawOption[ 'value_options' ] as $aValueOptions ) {
						$aLegacyRawOption[ 'value_options' ][ $aValueOptions[ 'value_key' ] ] = $aValueOptions[ 'text' ];
					}
				}

				$aLegacyRawOption[ 'info_link' ] = isset( $aRawOption[ 'link_info' ] ) ? $aRawOption[ 'link_info' ] : '';
				$aLegacyRawOption[ 'blog_link' ] = isset( $aRawOption[ 'link_blog' ] ) ? $aRawOption[ 'link_blog' ] : '';
				$aLegacySection[ 'section_options' ][] = $aLegacyRawOption;
			}

			if ( count( $aLegacySection[ 'section_options' ] ) > 0 ) {
				$aLegacyData[ $nPosition ] = $aLegacySection;
			}
		}
		return $aLegacyData;
	}

	/**
	 * @return array
	 */
	public function getAdditionalMenuItems() {
		return $this->getRawData_MenuItems();
	}

	/**
	 * @return string
	 */
	public function getNeedSave() {
		return $this->bNeedSave;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		$aOptionsValues = $this->getAllOptionsValues();
		if ( !isset( $aOptionsValues[ $sOptionKey ] ) ) {
			$this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey, $mDefault ), true );
		}
		return $this->aOptionsValues[ $sOptionKey ];
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public function getOptDefault( $sOptionKey, $mDefault = null ) {
		$aOptions = $this->getRawData_AllOptions();
		foreach ( $aOptions as $aOption ) {
			if ( $aOption[ 'key' ] == $sOptionKey ) {
				if ( isset( $aOption[ 'value' ] ) ) {
					return $aOption[ 'value' ];
				}
				elseif ( isset( $aOption[ 'default' ] ) ) {
					return $aOption[ 'default' ];
				}
			}
		}
		return $mDefault;
	}

	/**
	 * @param         $sKey
	 * @param mixed   $mValueToTest
	 * @param bool    $bStrict
	 * @return bool
	 */
	public function getOptIs( $sKey, $mValueToTest, $bStrict = false ) {
		$mOptionValue = $this->getOpt( $sKey );
		return $bStrict ? $mOptionValue === $mValueToTest : $mOptionValue == $mValueToTest;
	}

	/**
	 * @return string
	 */
	public function getOptionsKeys() {
		if ( !isset( $this->aOptionsKeys ) ) {
			$this->aOptionsKeys = [];
			foreach ( $this->getRawData_AllOptions() as $aOption ) {
				$this->aOptionsKeys[] = $aOption[ 'key' ];
			}
		}
		return $this->aOptionsKeys;
	}

	/**
	 * @return string
	 */
	public function getOptionsName() {
		return $this->sOptionsName;
	}

	/**
	 * @return string
	 */
	public function getOptionsStorageKey() {
		return $this->sOptionsStorageKey;
	}

	/**
	 * @return array
	 */
	public function getStoredOptions() {
		try {
			return $this->loadOptionsValuesFromStorage();
		}
		catch ( \Exception $e ) {
			return [];
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getRawData_FullFeatureConfig() {
		return $this->aRawOptionsConfigData ?? $this->aRawOptionsConfigData = $this->readConfiguration();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 * @throws \Exception
	 */
	protected function getRawData_AllOptions() {
		return $this->getRawData_FullFeatureConfig()[ 'options' ] ?? [];
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 * @throws \Exception
	 */
	protected function getRawData_Requirements() {
		return $this->getRawData_FullFeatureConfig()[ 'requirements' ] ?? [];
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 * @throws \Exception
	 */
	protected function getRawData_MenuItems() {
		return $this->getRawData_FullFeatureConfig()[ 'menu_items' ] ?? [];
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @param string $key
	 * @throws \Exception
	 */
	public function getRawData_SingleOption( $key ) {
		$allRawOptions = $this->getRawData_AllOptions();
		foreach ( \is_array( $allRawOptions ) ? $allRawOptions : [] as $opt ) {
			if ( isset( $opt[ 'key' ] ) && ( $key == $opt[ 'key' ] ) ) {
				return $opt;
			}
		}
		return null;
	}

	/**
	 * @return bool
	 */
	public function getRebuildFromFile() {
		return $this->bRebuildFromFile;
	}

	/**
	 * @param string $sOptionKey
	 * @return bool
	 */
	public function resetOptToDefault( $sOptionKey ) {
		return $this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey ), true );
	}

	/**
	 * @param string $sKey
	 */
	public function setOptionsStorageKey( $sKey ) {
		$this->sOptionsStorageKey = $sKey;
	}

	/**
	 * @return bool
	 */
	public function getIfLoadOptionsFromStorage() {
		return $this->bLoadFromSaved;
	}

	/**
	 * @param bool $bLoadFromSaved
	 */
	public function setIfLoadOptionsFromStorage( $bLoadFromSaved ) {
		$this->bLoadFromSaved = $bLoadFromSaved;
	}

	/**
	 * @param bool $bNeed
	 */
	public function setNeedSave( $bNeed ) {
		$this->bNeedSave = $bNeed;
	}

	/**
	 * @param bool $bRebuild
	 */
	public function setRebuildFromFile( $bRebuild ) {
		$this->bRebuildFromFile = $bRebuild;
	}

	/**
	 * @param array $aOptions
	 * @return $this
	 */
	public function setMultipleOptions( $aOptions ) {
		if ( is_array( $aOptions ) ) {
			foreach ( $aOptions as $sKey => $mValue ) {
				$this->setOpt( $sKey, $mValue );
			}
		}
		return $this;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mValue
	 * @param bool   $bForce
	 * @return mixed
	 */
	public function setOpt( $sOptionKey, $mValue, $bForce = false ) {

		if ( $bForce || $this->getOpt( $sOptionKey ) !== $mValue ) {
			$this->setNeedSave( true );

			//Load the config and do some pre-set verification where possible. This will slowly grow.
			$aOption = $this->getRawData_SingleOption( $sOptionKey );
			if ( !empty( $aOption[ 'type' ] ) ) {
				if ( $aOption[ 'type' ] == 'boolean' && !is_bool( $mValue ) ) {
					return $this->resetOptToDefault( $sOptionKey );
				}
			}
			$this->trackOption( $sOptionKey );
			$this->aOptionsValues[ $sOptionKey ] = $mValue;
		}
		return true;
	}

	/**
	 * Will return an option value to the original value if it was changed in this page load.
	 * @param string $sKey
	 * @return bool
	 */
	public function revertChangedOption( $sKey ) {
		if ( !empty( $this->aChangedOptionsTracker ) && is_array( $this->aChangedOptionsTracker ) && isset( $this->aChangedOptionsTracker[ $sKey ] ) ) {
			return $this->setOpt( $sKey, $this->aChangedOptionsTracker[ $sKey ] );
		}
		return false;
	}

	/**
	 * @param string $sKey
	 */
	private function trackOption( $sKey ) {
		if ( !isset( $this->aChangedOptionsTracker ) ) {
			$this->aChangedOptionsTracker = [];
		}
		// Meaning we only track once, and we don't overwrite if an option is set multiple times.
		if ( !isset( $this->aChangedOptionsTracker[ $sKey ] ) && isset( $this->aOptionsValues[ $sKey ] ) ) {
			$this->aChangedOptionsTracker[ $sKey ] = $this->aOptionsValues[ $sKey ];
		}
	}

	/**
	 * @param string $sOptionKey
	 * @return mixed
	 */
	public function unsetOpt( $sOptionKey ) {

		unset( $this->aOptionsValues[ $sOptionKey ] );
		$this->setNeedSave( true );
		return true;
	}

	/** PRIVATE STUFF */

	private function cleanOptions() {
		if ( empty( $this->aOptionsValues ) || !is_array( $this->aOptionsValues ) ) {
			return;
		}
		foreach ( $this->aOptionsValues as $sKey => $mValue ) {
			if ( !$this->getIsValidOptionKey( $sKey ) ) {
				$this->setNeedSave( true );
				unset( $this->aOptionsValues[ $sKey ] );
			}
		}
	}

	/**
	 * @param bool $bReload
	 * @return array|mixed
	 * @throws Exception
	 */
	private function loadOptionsValuesFromStorage( $bReload = false ) {

		if ( $bReload || empty( $this->aOptionsValues ) ) {

			if ( $this->getIfLoadOptionsFromStorage() ) {

				$sStorageKey = $this->getOptionsStorageKey();
				if ( empty( $sStorageKey ) ) {
					throw new Exception( 'Options Storage Key Is Empty' );
				}
				$this->aOptionsValues = $this->loadWP()->getOption( $sStorageKey, [] );
			}
		}
		if ( !is_array( $this->aOptionsValues ) ) {
			$this->aOptionsValues = [];
			$this->setNeedSave( true );
		}
		return $this->aOptionsValues;
	}

	/**
	 * @return array
	 */
	private function readConfiguration() {
		$WP = $this->loadWP();

		$cfg = $WP->getOption( $this->getConfigStorageKey() );

		$rebuild = true;//$this->getRebuildFromFile() || empty( $cfg );
		if ( !$rebuild && !empty( $cfg ) && is_array( $cfg ) ) {

			if ( !isset( $cfg[ 'meta_modts' ] ) ) {
				$cfg[ 'meta_modts' ] = 0;
			}
			$rebuild = $this->getConfigModTime() > $cfg[ 'meta_modts' ];
		}

		if ( $rebuild ) {
			try {
				$cfg = $this->readConfigurationJson();
			}
			catch ( \Exception $e ) {
				$cfg = [];
			}
			$cfg[ 'meta_modts' ] = $this->getConfigModTime();
			$WP->updateOption( $this->getConfigStorageKey(), $cfg );
		}

		$this->setRebuildFromFile( $rebuild );
		return $cfg;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	private function readConfigurationJson() {
		$path = $this->getPathToConfig();
		if ( empty( $path ) || !$this->loadFS()->isFile( $path ) ) {
			throw new Exception( sprintf( 'Configuration file "%s" does not exist.', esc_html( $path ) ) );
		}

		$cfg = \json_decode( $this->loadDP()->readFileContentsUsingInclude( $path ), true );
		if ( empty( $cfg ) ) {
			throw new Exception( sprintf( 'Reading JSON configuration from file "%s" failed.', esc_html( $path ) ) );
		}
		return $cfg;
	}

	private function getConfigStorageKey() :string {
		return 'icwp_app_'.\md5( $this->getPathToConfig() );
	}

	/**
	 * @return int|null
	 */
	protected function getConfigModTime() {
		return $this->loadFS()->getModifiedTime( $this->getPathToConfig() );
	}

	/**
	 * @return string
	 */
	public function getPathToConfig() {
		return dirname( __FILE__ ).'/../'.sprintf( 'config/feature-%s.php', $this->getOptionsName() );
	}

	/**
	 * @return string
	 * @deprecated 3.7
	 */
	private function getSpecTransientStorageKey() {
		return $this->getConfigStorageKey();
	}

	/**
	 * @return string
	 * @deprecated 3.7
	 */
	private function getConfigFilePath() {
		return $this->getPathToConfig();
	}
}