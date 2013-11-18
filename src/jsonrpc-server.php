<?php

/*
 * Basic JSONRPC Server Implementation, transport protocol agnostic
 */
class JsonRpcServer {

	// Supported jsonrpc protocol version
	const JSONRPC_VERSION = '2.0';
	
	// set to true to enable logging via 'error_log'
	public $log = false;
	
	// Namespace separator
	public $namespaceSeparator = '.';

	private $namespaces = array();

	public function register($handler, $namespace='default') {
		$this->namespaces[$namespace] = $handler;
	}

	public function unregister($namespace='default') {
		unset($this->$namespaces[$namespace]);
	}

	public function exec($requestStr) {
		$request = json_decode($requestStr);
		$response = null;

		if ($request === null) {// parsing error		
			$response = json_encode(self::error(null, -32700, 'Parse error'));
		}
		else if(!is_array($request)) { // Single request
			$response = json_encode(self::execSingleRequest($request));
		}
		else { // Batch request
			if(sizeof($request)==0) { // batch requests have to contain one or more requests
				$response = json_encode(self::error(null, -32600, 'Invalid request'));
			}
			else { // ok, there is some request
				$rlist = array();
				foreach($request as $r) {
					$singleResult = self::execSingleRequest($r);
					if($singleResult!=null) { 
						$rlist []= $singleResult;
					}
				}
				if(sizeof($rlist)>0) {
					$response = json_encode($rlist);
				}
			}
		}
		
		if($this->log==true) error_log("REQUEST=$requestStr, RESPONSE=$response");
		return $response;
	}

	public function execSingleRequest($request) {

		if(!is_object($request)) {
			return self::error(null, -32600, 'Invalid request');
		}
		
		$requestReflection = new ReflectionObject($request);
		$id = NULL;
		if ($requestReflection->hasProperty('id')) {
			$id = $request->id;
		}
		
		$params = null;
		if ($requestReflection->hasProperty('params')) {
			$params = $request->params;
		}
		else {
			$params = array();
		}

		// check jsonrpc version
		if(!$requestReflection->hasProperty('jsonrpc')) {
			return self::error($id, -32600, 'Invalid request');
		}
		else if ($request->jsonrpc != self::JSONRPC_VERSION) {
			return self::error($id, -32600, 'Unsupported jsonrpc version');
		}
		else
			try {
			$namespace = 'default';
			$method = explode($this->namespaceSeparator, $request->method, 2);
			if(sizeof($method)>1) {
				$namespace = $method[0];
				$method = $method[1];
			}
			else {
				$method = $method[0];
			}
			if(!isset($this->namespaces[$namespace])) {
				return self::error($id, -32601, 'Method not found (namespace)');
			}
			$object = $this->namespaces[$namespace];
			$handler = array($object, $method);
			if(!is_array($params) && !is_object($params)) {
				return self::error($id, -32600, 'Invalid request');
			}			
			if (is_callable($handler)) {
				
				if(is_object($params)) { // named parameters to positional parameters conversion
					$refMethod = new ReflectionMethod(get_class($object), $method);
					$refParams = $refMethod->getParameters();
					$convertedParams = array();
					foreach($refParams as $p) {
						$pname = $p->name;
						$convertedParams []= $params->$pname;
					}
					$params = $convertedParams;
				}
				
				$result = call_user_func_array($handler, $params);
				if($id!=null) {
					return self::success($id, $result);
				}
				else {
					return null;
				}
			} else {
				return self::error($id, -32601, 'Method not found');
			}
		} catch (Exception $e) {
			return self::error($id, -32603, $e->getMessage());
		}

	}

	private static function error($id, $code, $message = NULL, $data = NULL) {
		$r = array(
				'jsonrpc' => self::JSONRPC_VERSION,
				'error' => array(
						'code' => $code,
						'message' => $message
				)
		);
		if($data != NULL) {
			$r['data'] = $data;
		}
		if ($id != NULL) {
			$r['id'] = $id;
		}
		return $r;
	}

	private static function success($id, $result) {
		$r = array(
				'jsonrpc' => self::JSONRPC_VERSION,
				'result' => $result
		);
		if ($id != NULL) {
			$r['id'] = $id;
		}
		return $r;
	}

}

class HttpJsonRpcServer extends JsonRpcServer {
	
	public $postEnabled = true;
	public $getEnabled = false;
	
	public function httpExec() {
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header('content-type: text/javascript'); //header('content-type: application/json');
	
		$httpResponseCode = 200;
		$request = null;
	
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->postEnabled==true) {
			$request = file_get_contents('php://input');
		} else if ($_SERVER['REQUEST_METHOD'] == 'GET' && $this->getEnabled==true) {
			if(isset($_SERVER['QUERY_STRING'])) {
				$request = urldecode($_SERVER['QUERY_STRING']);
			}
		} else {
			$GLOBALS['http_response_code'] = 405;
			return json_encode(self::error(null, -32700, 'unsupported http method'));
		}
	
		$response = self::exec($request);
		echo $response;
	}
	
}