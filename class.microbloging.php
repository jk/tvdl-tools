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
		elseif ($service == 'identica') {
			// Identi.ca
			// curl -u username:password -d status="Your message here"
			// 		-k https://identi.ca/api/statuses/update.xml
			
			$url = 'https://identi.ca/api/statuses/update.xml';
			$authString = $username.":".$passwd;
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->curl, CURLOPT_POST, 1);
			curl_setopt($this->curl, CURLOPT_USERPWD, $authString);
		}
	}
	
	function short($string, $len)
	{
		if (strlen($string) < $len)
			return $string;

		return substr($string, 0, $len-1)."…";
	}
	
	function send($message) {
		$message = short($message, 140);
		if ($this->service == 'twitter') {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'status='.$message);
			$this->buffer = curl_exec($this->curl);
		}
		elseif ($this->service == 'identica') {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'status='.$message);
			$this->buffer = curl_exec($this->curl);
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