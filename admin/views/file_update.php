<?php

//set headers to NOT cache a page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 2000 05:00:00 GMT"); // Date in the past

require_once('../../../../../wp-load.php');

$uploads = wp_upload_dir();
$import_data_dir=$uploads['basedir'] . '/sailthru-import-data';
if (! is_dir($import_data_dir)){
	mkdir($import_data_dir);
}
$blog_id = get_current_blog_id();
$filename = $import_data_dir."/log_".$blog_id.".txt";

//error_log("log file name::".$filename);

if($_REQUEST["command"] == "print"){

	/*$fhandle = fopen($filename,'r');
	  $content = fread($fhandle, filesize($filename));
	  echo $content;*/

	if($_REQUEST["command"] == "print"){

		if (!file_exists($filename)) {
			if ( ( $f = @fopen($filename, 'w') ) !== FALSE ) {
				$handle = fopen($filename,'w');
			}

		}

		if ( ( $f = @fopen($filename, 'r') ) !== FALSE ) {

			while (!feof($f)) {
				$buffer = fread($f, 1024);
				echo $buffer;
				$buffer = NULL;
				unset($buffer);
			}

		}

	}

}