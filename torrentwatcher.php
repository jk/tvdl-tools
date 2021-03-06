#!/usr/bin/php
<?php
require_once('inc.functions.php');
check_for_configfile();
require_once('class.microbloging.php');

$config = simplexml_load_file(absolute_path('config.xml'));

$microbloging = new Microbloging($config->microbloging->service, 
	$config->microbloging->username,
	$config->microbloging->password);

define("FEED", $config->watcher->feeds->feed[0]);
define("WATCHFOLDER", absolute_path($config->watcher->watchfolder));
define("IDFILE", absolute_path($config->watcher->idfile));
define("DEBUG", $config->watcher->debug->attributes()->active);

// @TODO: Code gibt es nur aus Kompatibilitätsgründen
$shows = array();
foreach ($config->watcher->shows->show as $thisShows) {
	$shows[] = (string)$thisShows->regexp[0];
}
	

if (count($argv) > 1) {
	if ($argv[1] == '-t') {
		$testStrings = array(
			'Flashpoint - PREAiR 2x1 [PDTV - DINA]',
			'Greys Anatomy 5x9 [720P - HDTV - CTU]',
			'The Office 5x8 [HDTV - LOL]',
			'30 Rock 3x4 [HDTV - REPACK - LOL]',
			'ER 15x8 [HDTV - XOR]',
			'CSI 9x7 [720P - HDTV - DIMENSION]',
			'My Name Is Earl 4x11 [HDTV - LOL]',
			'Smallville 8x10 [720P - HDTV - CTU]',
			'Greys Anatomy 5x9 [HDTV - 0TV]',
			'30 Rock 3x4 [HDTV - LOL]'
		);
		writeLog("Testmodus (".count($testStrings)." Test-Strings) …\n");
		define('TEST', 1);
	} else {
		writeLog('Teste "'. $argv[1]."\" …");
		foreach ($shows as $show) {
			if (preg_match($show, $argv[1], $foo)) {
				writeLog("MATCH \"".$show."\"");
			}
		}
		die();
	}
} else {
	writeLog("Durchsuche ". FEED . " nach ".count($shows)." Serien …");
	$xml = simplexml_load_file(FEED);
}


// Dupletten-Filehandling
$ids = array();
if (DEBUG < 2 || TEST != 1) {
if (!file_exists(IDFILE)) {
	$fp = fopen(IDFILE, 'w+') or exit("ERROR: ID Cache kann nicht geöffnet werden. Datei: ".IDFILE."\n");
	fwrite($fp, '0');
	fclose($fp);
}
else {
	$fp = fopen(IDFILE, 'r') or exit("ERROR: ID Cache kann nicht geöffnet werden. Datei: ".IDFILE."\n");
	$content = fread($fp, filesize(IDFILE));
	$ids = explode(";", $content);
	fclose($fp);
}
}

if (TEST == 1) {
	$found = 0;
	foreach($testStrings as $item) {
		//writeLog($item);
		foreach ($shows as $show) {
			//writeLog($show);
			//echo $show . ": ".$item->title."\n";
			if (preg_match($show, $item, $foo)) {
				writeLog("MATCH $item ::: auf $show");
				$found++;
				break;
			}
		}	
	}
	die("\n==> ".$found . " gematcht.\n");
}


foreach($xml->channel->item as $item) {
	//echo $item->title."\n";
	if ($item->enclosure['type'] != 'application/x-bittorrent')
		continue;
	
	$valid = false;
	foreach ($shows as $show) {
		//echo $show . ": ".$item->title."\n";
		if (preg_match($show, $item->title, $foo)) {
			$valid = true;
			break;
		}
	}
	
	if (!$valid)
		continue;
	
	preg_match('/\/([0-9]+)$/i', $item->enclosure['url'], $treffer);
	$id = $treffer[1];
	
	if (in_array($id, $ids))
		continue;
		
	$datetime = strtotime($item->pubDate);
	
	if (!preg_match('/Show Name: ([A-Za-z0-9äöüß\/\-\ ]+);(.+?); Season: (\d+); Episode: (\d+)/i', $item->description, $treffer))
		continue;
	$title = $treffer[1];
	$season = $treffer[3];
	$episode = $treffer[4];
	
	$filename = strftime("%Y-%m-%d ", $datetime) . $title . " ".$season."x".sprintf("%02d",$episode).".torrent";
	$filename = str_replace('/', '-', $filename);
	$filename = str_replace('\\', '-', $filename);
	$filename = str_replace(' ', '_', $filename);
	$filename = WATCHFOLDER.'/'.$filename."\n";
	
	//download($item->enclosure['url'], $filename);
	$url = $item->enclosure['url'];
	writeLog("URL: ".$url);
	$gTitle = $title . " ".$season."x".sprintf("%02d", $episode);
	`growlnotify -t "$gTitle" -m "Neue $title-Folge als Torrent verfügbar und im Watchfolder gespeichert." -I ~/Downloads/`;
	`curl $url --output $filename`;
	
	$microbloging->send($title.' S'.sprintf('%02d', $season).'E'.sprintf('%02d', $episode).' gefunden. #tvdl');
	
	$ids[] = $id; // ID cachen
	
	if (DEBUG < 2)
	{
		// IDs cachen
		$fp = fopen(IDFILE, 'w+') or exit("ERROR: Konnte IDs nicht cachen. Datei: ".IDFILE."\n");
		fwrite($fp, implode(';', $ids));
		fclose($fp);
		// Transmission starten
		`open /Applications/Transmission.app`;
	}
}

?>