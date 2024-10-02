<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Collect;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

abstract class Base extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base {

	abstract public function collect() :array;

	protected function checkCanWordpressWrite() :bool {
		$url = '';
		$url = wp_nonce_url( $url, '' );

		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, false );
		ob_end_clean();

		if ( $creds === false ) {
			return false;
		}

		return (bool)\WP_Filesystem( $creds );
	}

	protected function checkCanWrite() :bool {
		$FS = $this->loadFS();

		$testDir = \dirname( __FILE__ ).'/icwp_test/';
		$testFile = $testDir.'test_write';
		$testContent = '#FINDME-'.\uniqid();

		$soFar = true;
		$outsMessage = '';

		if ( !$FS->mkdir( $testDir ) || !$FS->isDir( $testDir ) ) {
			$outsMessage = sprintf( 'Failed to create directory: %s', $testDir );
			$soFar = false;
		}
		if ( $soFar && !is_writable( $testDir ) ) {
			$outsMessage = sprintf( 'The test directory is not writable: %s', $testDir );
			$soFar = false;
		}
		if ( $soFar && !$FS->touch( $testFile ) ) {
			$outsMessage = sprintf( 'Failed to touch "%s"', $testFile );
			$soFar = false;
		}
		if ( $soFar && !file_put_contents( $testFile, $testContent ) ) {
			$outsMessage = sprintf( 'Failed to write content "%s" to "%s"', $testFile, $testContent );
			$soFar = false;
		}
		if ( $soFar && !@is_file( $testFile ) ) {
			$outsMessage = sprintf( 'Failed to find file "%s"', $testFile );
			$soFar = false;
		}
		$content = $FS->getFileContent( $testFile );
		if ( $soFar && ( $content != $testContent ) ) {
			$outsMessage = sprintf( 'The content "%s" does not match what we wrote "%s"', $content, $testContent );
			$soFar = false;
		}

		if ( !$soFar ) {
			$this->getStandardResponse()
				 ->setErrorMessage( $outsMessage );
			return false;
		}

		FileSystem::Instance()->deleteDir( $testDir );

		return true;
	}

	/**
	 * @param string $context
	 * @return mixed
	 */
	protected function getAutoUpdates( $context = 'plugins' ) {
		return \ICWP_Plugin::GetAutoUpdatesSystem()->getAutoUpdates( $context );
	}
}