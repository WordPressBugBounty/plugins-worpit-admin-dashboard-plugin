<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Utility;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class LocateFilesForType {

	public function find( string $workingDir, string $type ) :array {
		return \array_filter(
			WorpdriveRuntime::host()->filesystem()->enumItemsInDir( $workingDir ),
			fn( $path ) => \str_contains( \basename( $path ), FileNameFor::For( $type ) ) && \is_file( $path )
		);
	}
}
