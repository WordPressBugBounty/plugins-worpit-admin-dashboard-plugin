<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map\Listing;

use FernleafSystems\WorpdriveClient\Utility\DynPropertiesClass;

/**
 * @property string $path
 * @property string $type
 * @property string $hash
 * @property string $hash_alt
 * @property int    $mtime
 * @property int    $size
 */
class FileListItem extends DynPropertiesClass {

	public function __get( string $key ) {
		$value = parent::__get( $key );
		switch ( $key ) {
			case 'mtime':
			case 'size':
				$value = (int)$value;
				break;
			case 'hash':
			case 'hash_alt':
				$value = (string)$value;
				break;
			default:
				break;
		}
		return $value;
	}
}
