#!/usr/bin/php
<?php
if ($argc != 2 && $argc < 2) {
?>
Argument error (argument is missing).
<?php
} else if ($argc != 2 && 2 < $argc) {
?>
Argument error (argument exceeded).
<?php
} else {
	if ( !file_exists($argv[1]) ) {
?>
The specified path does not exist.
<?php
	} else {
		$res_directory = "vendor/pickles2/lib-indigo/res/";
		dir_copy($res_directory, $argv[1]);
	}
}

function dir_copy($src_dir, $dist_dir) {
	if (!is_dir($dist_dir)) {
		mkdir($dist_dir);
	}

	if (is_dir($src_dir)) {
		if ($dh = opendir($src_dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file == "." || $file == "..") {
					continue;
				}
				if (is_dir($src_dir . "/" . $file)) {
					dir_copy($src_dir . "/" . $file, $dist_dir . "/" . $file);
				} else {
					copy($src_dir . "/" . $file, $dist_dir . "/" . $file);
				}
			}
			closedir($dh);
		}
	}
	return true;

}
?>