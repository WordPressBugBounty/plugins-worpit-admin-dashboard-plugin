<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Utility;

use FernleafSystems\WorpdriveClient\Host\WorpdriveRuntime;

class DeletePreExistingFilesForType {

	public function delete( string $workingDir, string $type ) :void {
		\array_map(
			fn( string $path ) => WorpdriveRuntime::host()->filesystem()->deleteFile( $path ),
			( new LocateFilesForType() )->find( $workingDir, $type )
		);
	}
}
