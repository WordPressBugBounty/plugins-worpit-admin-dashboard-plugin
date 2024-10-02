<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Shield\Options;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\Shield\Controller\Plugin\PluginNavs;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\HackGuard\Scan\Results\Counts;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\HackGuard\Lib\FileLocker;

class Export extends \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Shield\Base {

	public function process() :ApiResponse {
		if ( !$this->isInstalled() ) {
			return $this->success( [
				'version' => 'not-installed' // \iControlWP\Shield\ShieldPluginConnectionStatus::REMOTE_NOT_INSTALLED
			] );
		}

		$con = $this->getShieldController();
		$urls = [];

		if ( \version_compare( $con->cfg->version(), '20.0', '>=' ) ) {
			$pluginURLs = $con->plugin_urls;
			$urls = [
				'overview'      => $pluginURLs->adminHome(),
				'scans_run'     => $pluginURLs->adminTopNav( PluginNavs::NAV_SCANS, PluginNavs::SUBNAV_SCANS_RUN ),
				'scans_results' => $pluginURLs->adminTopNav( PluginNavs::NAV_SCANS, PluginNavs::SUBNAV_SCANS_RESULTS ),
				'audit_trail'   => $pluginURLs->adminTopNav( PluginNavs::NAV_ACTIVITY, PluginNavs::SUBNAV_LOGS ),
				'ips'           => $pluginURLs->adminTopNav( PluginNavs::NAV_IPS, PluginNavs::SUBNAV_IPS_RULES ),
			];
		}

		// store the Shield Central license
		$this->updateProStatus();

		return $this->success( [
			'version'          => $con->cfg->version(),
			'is_pro'           => $con->isPremiumActive() ? 1 : 0,
			'urls'             => $urls,
			'scan_results'     => $this->countItemsForEachScan(),
			'exported_options' => $this->exportOptions()
		] );
	}

	private function exportOptions() :array {
		$con = $this->getShieldController();
		$options = [];
		if ( $con->cfg->configuration !== null ) {
			$opts = $con->opts;
			foreach ( \array_keys( $con->cfg->configuration->modules ) as $modSlug ) {
				$options[ $modSlug ] = [];
				foreach ( $con->cfg->configuration->optsForModule( $modSlug ) as $opt ) {
					$options[ $modSlug ][ $opt[ 'key' ] ] = $opts->optGet( $opt[ 'key' ] );
				}
			}
		}
		return $options;
	}

	private function countItemsForEachScan() :array {
		if ( \version_compare( $this->getShieldController()->cfg->version(), '18.0', '>=' ) ) {
			$counts = ( new Counts() )->all();
			$counts[ 'file_locker' ] = \count( ( new FileLocker\Ops\LoadFileLocks() )->withProblems() );
		}
		else {
			// file locker is a separate scan
			$counts = [];
		}
		return $counts;
	}

	private function updateProStatus() {
		$con = $this->getShieldController();
		if ( !$con->isPremiumActive() && $con->comps !== null ) {
			try {
				$con->comps->license->verify( true );
				$con->comps->api_token->setCanRequestOverride( true )->getToken();
			}
			catch ( \Exception $e ) {
			}
		}
	}
}