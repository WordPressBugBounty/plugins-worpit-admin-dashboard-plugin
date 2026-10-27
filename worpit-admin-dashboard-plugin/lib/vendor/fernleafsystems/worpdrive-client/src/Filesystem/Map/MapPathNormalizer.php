<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map;

class MapPathNormalizer {

	/**
	 * On Windows every backslash in a path is a directory separator. Elsewhere a backslash is an ordinary
	 * character that can appear in a file or directory name (e.g. "Don\'t Ask.jpg"), so it must be kept.
	 */
	private bool $backslashIsSeparator;

	/**
	 * @param bool|null $backslashIsSeparator null to follow the platform PHP runs on; tests pass true or false
	 */
	public function __construct( ?bool $backslashIsSeparator = null ) {
		$this->backslashIsSeparator = $backslashIsSeparator ?? \DIRECTORY_SEPARATOR === '\\';
	}

	public function normaliseRelativeToRoot( string $path, string $rootDir ) :string {
		$normalPath = $this->normalisePathSeparators( $path );
		$normalRoot = $this->normalisePathSeparators( $rootDir );
		$untrailedPath = $this->untrailForwardSlash( $normalPath );
		$untrailedRoot = $this->untrailForwardSlash( $normalRoot );

		if ( $untrailedPath === $untrailedRoot ) {
			return '';
		}

		$rootPrefix = $this->trailForwardSlash( $untrailedRoot );
		if ( $untrailedRoot !== '' && \str_starts_with( $normalPath, $rootPrefix ) ) {
			return \ltrim( \substr( $normalPath, \strlen( $rootPrefix ) ), '/' );
		}

		return \ltrim( $normalPath, '/' );
	}

	/**
	 * Converts directory separators to "/". Only backslashes that are separators are converted.
	 */
	public function normalisePathSeparators( string $path ) :string {
		return $this->backslashIsSeparator ? \str_replace( '\\', '/', $path ) : $path;
	}

	private function untrailForwardSlash( string $path ) :string {
		return \rtrim( $path, '/' );
	}

	private function trailForwardSlash( string $path ) :string {
		return $this->untrailForwardSlash( $path ).'/';
	}
}
