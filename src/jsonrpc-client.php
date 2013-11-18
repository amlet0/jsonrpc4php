<?php
 
/*
 * Basic JSONRPC Client Implementation
*/
class JsonRpcClient {

	// Supported jsonrpc protocol version
	const JSONRPC_VERSION = '2.0';
	
	// Current namespace
	public $namespace = null;
	
	// Namespace separator
	public $namespaceSeparator = '.';
	
	function __get($namespace) {
		$ns = clone $this;
		if($this->namespace==null) {
			$ns->namespace = $namespace;
		}
		else {
			$ns->namespace = $ns->namespace.$this->namespaceSeparator.$namespace;
		}
		return $ns;
	}
	
	function __call($name, $args) {
		return $this->execute(null, $name, $args);
	}
	
	function toJsonRpc($id, $method, $args) {
		if($id==null) {
			$id=1;
		};
		if($this->namespace!=null) {
			$method = $this->namespace.$this->namespaceSeparator.$method;
		}
		return json_encode(array(
				'jsonrpc' => self::JSONRPC_VERSION,
				'method' => $method,
				'id' => $id,
				'params' => $args
		));
	}
	
	function execute($id, $name, $args) {
		error_log("this method must be overridden");
		return null;
	}
	
}

class HttpJsonRpcClient extends JsonRpcClient {

	var $url = null;
	var $method = null;
	
	function __construct($url) {
		$this->url = $url;
	}
	
	function execute($id, $name, $args) {
		$js = $this->toJsonRpc($id, $name, $args);	
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $js);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
}