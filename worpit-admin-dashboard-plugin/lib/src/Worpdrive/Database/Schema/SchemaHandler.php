<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Schema;

use FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Operators\{
	Config,
	Exporter,
	TableEnum
};

class SchemaHandler extends \FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\BaseDbHandler {

	private array $tables;

	/**
	 * @throws \Exception
	 */
	public function run() :array {
		$this->tables = ( new TableEnum() )->enum();
		return [
			'tables'      => $this->tables,
			'schema_dump' => $this->dumpSchema(),
		];
	}

	/**
	 * @throws \Exception
	 */
	private function dumpSchema() :array {
		$cfg = ( new Config() )->applyDumpSchemaOptions();
		$cfg->set( 'tables', \array_keys( $this->tables ) );
		return ( new Exporter( $cfg ) )->export();
	}
}