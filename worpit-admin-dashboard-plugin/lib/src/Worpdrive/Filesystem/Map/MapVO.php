<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Filesystem\Map;

class MapVO {

	public string $dir;

	public array $exclusions = [];

	public string $hashAlgo = 'adler32';

	public int $newerThanTS = 0;

	public int $olderThanTS = 0;

	public int $maxFileSize = 200;
}