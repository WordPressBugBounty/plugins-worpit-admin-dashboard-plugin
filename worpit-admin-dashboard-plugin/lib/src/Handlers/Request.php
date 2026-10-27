<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class Request {

	protected static Request $I;

	private array $server;

	private string $input;

	public static function Instance() :self {
		return self::$I ??= new self();
	}

	public function __construct() {
		$this->server = \is_array( $_SERVER ) ? $_SERVER : [];
	}

	public function input() :string {
		return $this->input ??= (string)\file_get_contents( 'php://input' );
	}

	public function server( string $key ) {
		return $this->server[ $key ] ?? null;
	}
}