<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

class MapPathNormalizer {

	public function normaliseRelativeToRoot( string $path, string $rootDir ) :string {
		$normalPath = wp_normalize_path( $path );
		$normalRoot = wp_normalize_path( $rootDir );
		$untrailedPath = untrailingslashit( $normalPath );
		$untrailedRoot = untrailingslashit( $normalRoot );

		if ( $untrailedPath === $untrailedRoot ) {
			return '';
		}

		$rootPrefix = trailingslashit( $untrailedRoot );
		if ( $untrailedRoot !== '' && \str_starts_with( $normalPath, $rootPrefix ) ) {
			return \ltrim( \substr( $normalPath, \strlen( $rootPrefix ) ), '/' );
		}

		return \ltrim( $normalPath, '/' );
	}
}
