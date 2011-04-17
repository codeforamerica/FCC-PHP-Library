<?php
class Connection {
	private $_root, $_http;
	
	public function __construct($server, $http = false) {
		$this->_root = $server;
		if($http) {
			$this->_http = $http;
		} else {
			$this->_http = curl_init();
			curl_setopt($this->_http, CURLOPT_RETURNTRANSFER, 1);
		}
	}
	
	private function _request($path, $method, $data=false, $headers=false) {
		
		# URL encode any available data
        if ($data) {
			$query = http_build_query($data);
		}
		
		if(in_array(strtolower($method), array('get','delete'))) {
			# Add urlencoded data to the path as a query if method is GET or DELETE
			if($data) {
				$path = $path.'?'.$query;
			}

		} else {
			# If method is POST or PUT, put the query data into the body
			$body = ($data) ? $query : '';
			curl_setopt($this->_http, CURLOPT_POSTFIELDS, $body);
		}
		
		$url = $this->_root . $path;

		curl_setopt($this->_http, CURLOPT_URL, $url);
		if($headers) curl_setopt($this->_http, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($this->_http, CURLOPT_CUSTOMREQUEST, $method);

		$result = curl_exec($this->_http);
		

		if($result === false) {	
			echo 'Curl error: ' . curl_error($this->_http) . "\n";
		} 
		//curl_close($this->_http);
		
		return $result;
		
	}
	
	public function get($path, $data, $headers = null) {
		return $this->_request($path, 'GET', $data, $headers);
	}
	public function post($path, $data, $headers = null) {
		return $this->_request($path, 'POST', $data, $headers);
	}
	public function put($path, $data, $headers = null) {
		return $this->_request($path, 'PUT', $data, $headers);
	}
	public function delete($path, $data, $headers = null) {
		return $this->_request($path, 'DELETE', $data, $headers);
	}

}
?>