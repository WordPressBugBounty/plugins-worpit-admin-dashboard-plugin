<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Filesystem\Map\Listing;

class SqliteFileListing extends AbstractFileListing {

	public const TABLE_NAME_ITEMS = 'file_item';

	protected \SQLite3 $db;

	public function __construct( string $sqlitePath ) {
		parent::__construct( $sqlitePath );
		$this->db = new \SQLite3( $this->listingPath );
		$this->createTables();
	}

	public function __destruct() {
		$this->db->close();
	}

	public function startLargeListing() :void {
		$this->db->exec( 'BEGIN;' );
	}

	public function finishLargeListing( bool $successfulCreation ) :void {
		$this->db->exec( $successfulCreation ? 'COMMIT;' : 'ROLLBACK;' );
	}

	public function addRaw( string $path, string $hash = '', string $hashAlt = '', ?int $mtime = null, ?int $size = null ) :void {
		$this->db->exec( sprintf( 'INSERT OR IGNORE INTO `%s` VALUES (%s);',
			self::TABLE_NAME_ITEMS,
			sprintf( "'%s','%s','%s',%s,%s",
				\base64_encode( $this->normalisePath( $path ) ),
				$hash,
				$hashAlt,
				$mtime === null ? 0 : $mtime,
				(int)$size
			)
		) );
	}

	protected function createTables() :void {
		foreach ( $this->dbSpec() as $tableName => $tableSpec ) {
			$columns = [];
			foreach ( $tableSpec[ 'columns' ] as $col => $spec ) {
				$columns[] = "`$col` $spec";
			}
			$colsPart = \implode( ', ', $columns );
			$this->db->exec( "CREATE TABLE IF NOT EXISTS `{$tableName}` ({$colsPart});" );
		}
	}

	protected function dbSpec() :array {
		return [
			self::TABLE_NAME_ITEMS => [
				'columns' => [
					'path'     => 'TEXT NOT NULL UNIQUE',
					'hash'     => 'TEXT',
					'hash_alt' => 'TEXT',
					'mtime'    => 'INTEGER',
					'size'     => 'INTEGER',
				],
			],
		];
	}
}
