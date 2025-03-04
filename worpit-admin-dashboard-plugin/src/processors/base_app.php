<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\RequestParameters;

abstract class ICWP_APP_Processor_BaseApp extends ICWP_APP_Processor_Base {

	protected function getRequestParams() :RequestParameters {
		return $this->reqParams ??= new RequestParameters(
				   $this->loadDP()->FetchGet( 'reqpars', [] ),
				   $this->loadDP()->FetchPost( 'reqpars', [] )
			   );
	}
}