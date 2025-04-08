<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\ApiResponse;
use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\{
	Base,
	User,
	Worpdrive
};

class ICWP_APP_Processor_Plugin_Api_Internal extends \ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return ApiResponse
	 */
	protected function processAction() {
		$action = $this->getRequestParams()->action;
		if ( empty( $action ) || !$this->isActionSupported( $action ) ) {
			return $this->setErrorResponse( sprintf( 'Action "%s" is not currently supported.', $action ) );
		}
		return $this->processActionHandler();
	}

	/**
	 * @throws \Exception
	 */
	protected function findLegacyApiClass() :string {
		$action = $this->getRequestParams()->action;

		$class = $this->actionClassMap()[ $action ] ?? null;
		if ( empty( $class ) ) {
			$parts = \array_map( '\ucfirst', \explode( '_', $action ) );
			if ( \count( $parts ) < 2 ) {
				throw new Exception( sprintf( 'Unsupported Action Name: %s', esc_html( $action ) ) );
			}

			$class = '\\FernleafSystems\\Wordpress\\Plugin\\iControlWP\\LegacyApi\\Internal\\'.\implode( '\\', $parts );

			if ( !@\class_exists( $class ) ) {
				throw new Exception( sprintf( 'Class Not Found: %s', esc_html( $class ) ) );
			}
		}
		return $class;
	}

	protected function processActionHandler() :ApiResponse {
		try {
			$class = $this->findLegacyApiClass();
			/** @var Base $API */
			$API = new $class();
			$API->setRequestParams( $this->getRequestParams() )
				->setStandardResponse( $this->getStandardResponse() )
				->preProcess();
			$response = $API->process();
		}
		catch ( \Exception $e ) {
			$response = $this->setErrorResponse( $e->getMessage() );
		}
		return $response;
	}

	protected function isActionSupported( string $action ) :bool {
		return \in_array( $action, $this->mod->getSupportedInternalApiAction() );
	}

	protected function actionClassMap() :array {
		return [
			'user_list'                     => User\Enumerate::class,
			'worpdrive_db_data'             => Worpdrive\Db\Data::class,
			'worpdrive_db_schema'           => Worpdrive\Db\Schema::class,
			'worpdrive_filesystem_hashless' => Worpdrive\Filesystem\Hashless::class,
			'worpdrive_filesystem_map'      => Worpdrive\Filesystem\Map::class,
			'worpdrive_filesystem_recent'   => Worpdrive\Filesystem\Recent::class,
			'worpdrive_filesystem_zip'      => Worpdrive\Filesystem\Zip::class,
		];
	}
}