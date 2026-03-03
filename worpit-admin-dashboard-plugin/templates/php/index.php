<?php
if ( !empty( $mainFeatureInclude ) ) {
	$baseDirName = \dirname(__FILE__).'/';
	include_once( $baseDirName.'index_header.php' );
	include_once( $baseDirName.$mainFeatureInclude );
	include_once( $baseDirName.'index_footer.php' );
}