<?php
// Konstanten
define(DIR, dirname(__FILE__));
define(WARN, 'warn');
define(INFO, 'info');
define(NOTICE, 'notice');
define(NONE, 'none');
define(NIL, 'nil');
define(FAIL, 'error');
define(ERROR, 'error');

function absolute_path($file) {
	if (substr($file, 0, 1) == '/')
		return $file;
	elseif (substr($file, 0, 0) == '~')
		return getenv('HOME').substr($file, 1);
	else
		return dirname(__FILE__).'/'.$file;
}

function writeLog($msg, $type = 'none') {
	$out = strftime("[%H:%M:%S] ");
	
	if ($type != 'none')
		$out .= strtoupper($type).': ';
	
	echo  $out . $msg . "\n";
}

function download($url, $file) {
	$sh = curl_init($url);
	$hFile = fopen($file, 'w');
	curl_setopt($sh, CURLOPT_FILE, $hFile);
	curl_setopt($sh, CURLOPT_HEADER, 0);
	curl_exec($sh);
	curl_close($sh);
	fclose($hFile);
}

function check_for_configfile() {
	if(!file_exists(absolute_path('config.xml'))) {
		if(file_exists(absolute_path('cofig.xml-dist'))) {
			writeLog('Vor der Nutzung »config.xml-dist« in »config.xml« umbennen und anpassen.', FAIL);
			die();
		}
		else {
			writeLog('Konfigurationsdatei »config.xml« nicht vorhanden.', FAIL);
			die();
		}
	}
}
?>