<?php
define("DIR", dirname(__FILE__));
function absolute_path($file) {
	if (substr($file, 0, 1) == '/')
		return $file;
	elseif (substr($file, 0, 0) == '~')
		return getenv('HOME').substr($file, 1);
	else
		return dirname(__FILE__).'/'.$file;
}

function writeLog($msg) {
	echo strftime("[%H:%M:%S] ") . $msg . "\n";
}
?>