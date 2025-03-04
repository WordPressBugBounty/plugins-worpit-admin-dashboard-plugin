<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map\Listing;

use FernleafSystems\Wordpress\Plugin\iControlWP\Handlers\FileSystem;

class SqliteFileListing extends AbstractFileListing {

	public const TABLE_NAME_ITEMS = 'file_item';

	protected \SQLite3 $db;

	private string $sqlitePath;

	private bool $deleteOnDestruct;

	public function __construct( string $sqlitePath, bool $deleteOnDestruct = false, bool $recreateDB = false ) {
		$this->sqlitePath = $sqlitePath;
		$this->deleteOnDestruct = $deleteOnDestruct;
		if ( $recreateDB ) {
			FileSystem::Instance()->delete( $sqlitePath );
		}
		$this->db = new \SQLite3( $this->sqlitePath );
		$this->createTables();
		$this->useCache = false;
	}

	public function __destruct() {
		$this->db->close();
		if ( $this->deleteOnDestruct ) {
			@\unlink( $this->sqlitePath );
		}
	}

	public function createClone( bool $transientClone = true ) :AbstractFileListing {
		return $this->cloneToPath( $this->sqlitePath.'_clone', $transientClone );
	}

	public function cloneToPath( string $sqlitePath, bool $transientClone = true ) :SqliteFileListing {
		if ( \is_file( $sqlitePath ) ) {
			\unlink( $sqlitePath );
		}

		$this->db->close();

		\copy( $this->sqlitePath, $sqlitePath );
		$clone = new static( $sqlitePath, $transientClone );

		$this->db->open( $this->sqlitePath );
		return $clone;
	}

	public function startLargeListing() :void {
		$this->db->exec( 'BEGIN;' );
	}

	public function finishLargeListing( bool $successfulCreation ) :void {
		$this->db->exec( $successfulCreation ? 'COMMIT;' : 'ROLLBACK;' );
	}

	public function addRaw( string $path, string $hash = '', string $hashAlt = '', ?int $mtime = null, ?int $size = null ) :void {
		if ( !$this->exists( $path ) ) {
			$this->db->exec( sprintf( 'INSERT INTO `%s` VALUES (%s);',
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
	}

	public function get( string $path ) :?FileListItem {
		$item = null;
		$record = $this->db->querySingle(
			sprintf( "SELECT * FROM `%s` WHERE `path`='%s';", self::TABLE_NAME_ITEMS, \base64_encode( $this->normalisePath( $path ) ) ),
			true
		);
		if ( !empty( $record ) ) {
			$record[ 'path' ] = \base64_decode( $record[ 'path' ] );
			$item = ( new FileListItem() )->applyFromArray( $record );
		}
		return $item;
	}

	public function m_get( array $paths ) :array {
		$items = [];
		if ( !empty( $paths ) ) {
			$resultsSet = $this->db->query(
				sprintf( "SELECT * FROM `%s` WHERE `path` IN ('%s');",
					self::TABLE_NAME_ITEMS,
					\implode( "','", \array_map( fn( string $path ) => \base64_encode( $this->normalisePath( $path ) ), $paths ) )
				)
			);
			while ( $record = $resultsSet->fetchArray( \SQLITE3_ASSOC ) ) {
				$record[ 'path' ] = \base64_decode( $record[ 'path' ] );
				$items[ $record[ 'path' ] ] = ( new FileListItem() )->applyFromArray( $record );
			}
		}
		return $items;
	}

	public function m_remove( array $paths ) :bool {
		return empty( $paths ) ||
			   $this->db->exec(
				   sprintf( "DELETE FROM `%s` WHERE `path` IN ('%s');",
					   self::TABLE_NAME_ITEMS,
					   \implode( "','", \array_map( fn( string $path ) => \base64_encode( $this->normalisePath( $path ) ), $paths ) )
				   )
			   );
	}

	public function remove( string $path ) :bool {
		return $this->db->exec( sprintf( "DELETE FROM `%s` WHERE `path`='%s';", self::TABLE_NAME_ITEMS, \base64_encode( $this->normalisePath( $path ) ) ) );
	}

	public function total() :int {
		return (int)$this->db->query( sprintf( "SELECT COUNT(*) FROM `%s`;", self::TABLE_NAME_ITEMS ) )
							 ->fetchArray( \SQLITE3_NUM )[ 0 ];
	}

	public function getNewestItem() :?FileListItem {
		$item = $this->db->querySingle( sprintf( "SELECT * FROM `%s` ORDER BY `mtime` DESC LIMIT 1;", self::TABLE_NAME_ITEMS ), true );
		return $item ? ( new FileListItem() )->applyFromArray( $item ) : null;
	}

	public function totalFileSize() :int {
		return (int)$this->db->querySingle( sprintf( "SELECT SUM(`size`) as `total` from `%s`;", self::TABLE_NAME_ITEMS ) );
	}

	public function exists( string $path ) :bool {
		return (bool)$this->db->querySingle(
			sprintf( "SELECT exists(SELECT 1 FROM `%s` WHERE `path`='%s') AS `path_exists`;", self::TABLE_NAME_ITEMS, \base64_encode( $this->normalisePath( $path ) ) )
		);
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

	public function getPageSize() :int {
		return 500;
	}

	public function getPage( $pageNumber ) :array {
		$page = [];

		$resultsSet = $this->db->query(
			sprintf( "SELECT * FROM `%s` LIMIT %s OFFSET %s;",
				self::TABLE_NAME_ITEMS,
				$this->getPageSize(),
				$pageNumber*$this->getPageSize()
			)
		);
		if ( !empty( $resultsSet ) ) {
			while ( $record = $resultsSet->fetchArray( \SQLITE3_ASSOC ) ) {
				$record[ 'path' ] = \base64_decode( $record[ 'path' ] );
				$page[] = ( new FileListItem() )->applyFromArray( $record );
			}
		}
		return $page;
	}

	public function purge() :void {
		$this->dbClose();
		FileSystem::Instance()->delete( $this->dbPath() );
	}

	public function dbClose() :void {
		$this->db->close();
	}

	public function dbOpen() :void {
		$this->db->open( $this->sqlitePath );
	}

	public function dbPath() :string {
		return $this->sqlitePath;
	}
}