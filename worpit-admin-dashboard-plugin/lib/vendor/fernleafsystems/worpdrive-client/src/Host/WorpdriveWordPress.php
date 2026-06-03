<?php declare( strict_types=1 );

namespace FernleafSystems\WorpdriveClient\Host;

interface WorpdriveWordPress {

	public function plugins() :array;

	public function themes() :array;

	public function homeUrl() :string;

	public function wpUrl() :string;

	public function restUrl() :string;

	public function contentUrl() :string;

	public function locale() :string;

	public function timezoneString() :string;

	public function isMultisite() :bool;

	public function version() :string;

	public function scriptFilename() :string;
}
