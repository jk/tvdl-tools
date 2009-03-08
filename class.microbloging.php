<?php
setlocale(LC_TIME, "de_DE");

curl_close($curl);

class Microbloging {
	private $curl = null;
	private $service = 'twitter'; // Default: Twitter
	
	function __construct($service) {
		$this->service = $service;
		
		if ($service == 'twitter') {
			// Twitter
			$url = 'http://twitter.com/statuses/update.xml';
			$authString = $config->microbloging->username.":"$config->microbloging->password;
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_USERPWD, $authString);
		}
		elseif ($service == 'laconia') {
			// NOOP
		}
	}
	
	function short($string, $len)
	{
		if (strlen($string) < $len)
			return $string;

		return substr($string, 0, $len)."…";
	}
	
	function send($message) {
		if ($this->service == 'twitter') {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'status='.$message);
			$this->buffer = curl_exec($this->curl);
		}
		elseif ($this->service == 'laconia') {
			// NOOP
		}
		
		return $this->buffer;
	}
	
	function getBuffer() {
		return $this->buffer;
	}
	
	function __destruct() {
		curl_close($this->curl);
	}
}
?>