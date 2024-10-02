<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;

class Themes extends Base {

	private $hasChildThemes = false;

	public function process() :ApiResponse {
		return $this->success( [
			'wordpress-themes' => $this->loadWP()->getWordpressIsAtLeastVersion( '3.4' ) ? $this->collect() : [],
		] );
	}

	public function collect() :array {
		$this->hasChildThemes = false;
		$activeTheme = $this->loadWpFunctionsThemes()->getCurrent()->get_stylesheet();
		$autoUpdates = $this->getAutoUpdates( 'themes' );
		$updates = $this->loadWP()->updatesGather( 'themes', $this->isForceUpdateCheck() );

		/** @var \WP_Theme[] $themeObjects */
		$themes = \array_map(
			function ( $theme ) use ( $activeTheme, $autoUpdates, $updates ) {

				$stylesheet = $theme->offsetGet( 'Stylesheet' );
				$isChild = $theme->offsetGet( 'Template' ) != $theme->offsetGet( 'Stylesheet' );
				$this->hasChildThemes = $this->hasChildThemes || $isChild;

				$data = array_intersect_key( [
					'Name'           => $theme->display( 'Name' ),
					'Title'          => $theme->offsetGet( 'Title' ),
					'Description'    => $theme->offsetGet( 'Description' ),
					'Author'         => $theme->offsetGet( 'Author' ),
					'Author Name'    => $theme->offsetGet( 'Author Name' ),
					'Author URI'     => $theme->offsetGet( 'Author URI' ),
					'Version'        => $theme->offsetGet( 'Version' ),
					'Template'       => $theme->offsetGet( 'Template' ),
					'Stylesheet'     => $stylesheet,
					'Theme Root'     => $theme->offsetGet( 'Theme Root' ),
					'Theme Root URI' => $theme->offsetGet( 'Theme Root URI' ),

					'Status' => $theme->offsetGet( 'Status' ),

					'IsChild'        => $isChild ? 1 : 0,
					'IsParent'       => 0,

					// We add our own
					'network_active' => $theme->is_allowed( 'network' )
				], array_flip( $this->getDesiredThemeAttributes() ) );

				$data[ 'active' ] = $stylesheet === $activeTheme ? 1 : 0;
				$data[ 'auto_update' ] = \in_array( $stylesheet, $autoUpdates ) ? 1 : 0;
				$data[ 'update_available' ] = isset( $updates->response[ $stylesheet ] ) ? 1 : 0;
				$data[ 'update_info' ] = '';

				if ( $data[ 'update_available' ] ) {
					$updateInfo = $updates->response[ $data[ 'Stylesheet' ] ];
					if ( isset( $updateInfo[ 'sections' ] ) ) {
						unset( $updateInfo[ 'sections' ] ); // TODO: Filter unwanted data using set array of keys
					}
					$data[ 'update_info' ] = wp_json_encode( $updateInfo );
				}

				return $data;
			},
			$this->loadWpFunctionsThemes()->getThemes()
		);

		if ( $this->hasChildThemes ) {
			foreach ( $themes as $maybeChildTheme ) {
				if ( $maybeChildTheme[ 'IsChild' ] ) {
					foreach ( $themes as &$aMaybeParentTheme ) {
						if ( $aMaybeParentTheme[ 'Stylesheet' ] == $maybeChildTheme[ 'Template' ] ) {
							$aMaybeParentTheme[ 'IsParent' ] = 1;
						}
					}
				}
			}
		}

		return $themes;
	}

	protected function getDesiredThemeAttributes() :array {
		return [
			'Name',
			'Version',
			'Template',
			'Stylesheet',
			'IsChild',
			'IsParent',
			'Network',
			'active',
			'network_active'
		];
	}
}