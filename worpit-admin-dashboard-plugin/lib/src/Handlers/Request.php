<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Handlers;

class Request {

	protected static Request $I;

	private array $query;

	private array $post;

	private array $server;

	private array $env;

	private string $input;

	public static function Instance() :self {
		return self::$I ??= new self();
	}

	public function __construct() {
		$this->query = \is_array( $_GET ) ? $_GET : [];
		$this->post = \is_array( $_POST ) ? $_POST : [];
		$this->server = \is_array( $_SERVER ) ? $_SERVER : [];
		$this->env = \is_array( $_ENV ) ? $_ENV : [];
	}

	public function input() :string {
		return $this->input ??= (string)\file_get_contents( 'php://input' );
	}

	public function env( string $key ) {
		return $this->env[ $key ] ?? null;
	}

	public function post( string $key ) {
		return $this->post[ $key ] ?? null;
	}

	public function query( string $key ) {
		return $this->query[ $key ] ?? null;
	}

	public function server( string $key ) {
		return $this->server[ $key ] ?? null;
	}
}