#!/usr/bin/php
<?php
/**
 * Skript zum zusammensuchen von heruntergeladenen Serien. Weiterhin
 * werden die gefundenen Serien umbenannt und in eine Ordnerhierarchie
 * einsortiert. Ausserdem gibt es per Growl eine Benachrichtigung.
 *
 * @version 1.4 (2009-02-13)
 * @author Jens Kohl <jens.kohl@gmail.com>
 */

require_once('inc.functions.php');
check_for_configfile();

$config = simplexml_load_file(absolute_path('config.xml'));

define(LOGGING, $config->sorter->logging->attributes()->active);
define(RENAME_DIVX, $config->sorter->rename_divx->attributes()->active);

// Wenn das Skript auf der Kommandozeile mit dem Parameter now gestartet
// wird, dann gibt es keine Gracetime für neu hinzugefügte Dateien. Also
// aufpassen was ihr tut.
if($argv[1] == 'now') {
	define(NOW, true);
}

/**
 * Funktion verschriebt Dateien in in eine geparste Ordnerhierarchie
 *
 * @author Jens Kohl <jens.kohl@gmail.com>
 * @param string Dateiname
 * @param string Verzeichnisname
 * @return string Statusnachricht
 */
function move_file($in_file, $dir) {
	if (preg_match('/(.*?)(S(\d{1,2})E(\d{1,2})|(\d{1,2})x(\d{1,2})).*?\.(avi|divx|mkv|mov|wmv)$/i', $in_file, $treffer)) {
		//var_dump($treffer);
		
		global $config;
		
		$title   = $treffer[1];
		$title   = preg_replace('/(\.|-|_)/i', ' ', $title);
		$title	 = ucwords(trim($title));
		$season  = ($treffer[3]) ? (int) $treffer[3] : (int) $treffer[5];
		$episode = ($treffer[4]) ? (int) $treffer[4] : (int) $treffer[6];
		$extension = $treffer[7];
	
		if (RENAME_DIVX && preg_match('/xvid/i', $in_file)) {
			$extension = 'divx';
		}
		
		$moved = false;
		$filesize = filesize($dir.'/'.$in_file);
		foreach($config->sorter->destination->directory as $thisDest) {
			if (disk_free_space($thisDest) < $filesize) {
				# Ziellaufwerk zu klein
				writelog($thisDest.' verfügt nicht über genügend Platz für '.$in_file, WARN);
			}
			else
			{
				# Make the directory
				if(!file_exists($thisDest.'/'.$title.'/Season '.$season)) {
					if (!file_exists($thisDest.'/'.$title)) {
						mkdir($thisDest.'/'.$title);
					}

					mkdir($thisDest.'/'.$title.'/Season '.$season);
				}

				$new_file = $thisDest.'/'.$title."/Season ".$season."/".sprintf("%02dx%02d", $season, $episode)." - $title.$extension";
				rename($dir.'/'.$in_file, $new_file);
				writelog("$title $season x $episode nach $thisDest verschoben.", INFO);
				$moved = true;
				
				if(LOGGING) {
					// Logfile schreiben
					$logfile = $config->sorter->destination->directory[0]."/moviesorter.log";
					$logline = '['.strftime("%Y-%m-%d %H:%M")."] $title ({$season}x{$episode})\n";
					file_put_contents($logfile, $logline, FILE_APPEND | FILE_TEXT);
				}
				
				return "Moved $title ($season x $episode)\n";
			}
			
			if (!$moved) {
				writelog('Kein Platz für ' . $in_file ." vorhanden.", FAIL);
			}
		}
	}
}

$output = '';

foreach($config->sorter->sources->directory as $thisSource) {
	writelog($thisSource . ' überprüfen…', INFO);
	
	if ($handle = opendir($thisSource)) {
	    while (false !== ($file = readdir($handle))) {
			$fullfile = $thisSource.'/'.$file;
	        if (!is_dir($fullfile) && $file != '.' && $file != '..' && substr($file, 0, 1) != '.' ) {
				// Wenn Datei vor weniger als 20 Minuten modifiziert wurde, dann ueberspringen
				if (fileatime($fullfile) >= (mktime() - 60 * 20)) {
					if (NOW) {
						$ETA = fileatime($fullfile) - (mktime()-60*20);
						$ETA /= 60;
						$ETA = round($ETA);
						writelog("DELAYED ($ETA mins): $fullfile", INFO);
					}
					else {
						continue;
					}
				}

				$output .= move_file($file, $thisSource);
	        }
	    }
	    closedir($handle);
	}
}

//echo $output;
if ($output) {
	`growlnotify -t "Filme aufgeräumt" -m "$output" -I ~/Movies/`;
}

?>