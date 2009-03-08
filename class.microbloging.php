<?php
setlocale(LC_TIME, "de_DE");

class Microbloging {
	private $curl = null;
	private $service = 'twitter'; // Default: Twitter
	
	function __construct($service, $username, $passwd) {
		
		$this->service = $service;
		
		if ($service == 'twitter') {
			// Twitter
			$url = 'http://twitter.com/statuses/update.xml';
			$authString = $username.":".$passwd;
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->curl, CURLOPT_POST, 1);
			curl_setopt($this->curl, CURLOPT_USERPWD, $authString);
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