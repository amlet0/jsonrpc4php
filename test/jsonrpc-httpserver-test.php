<?php
 require_once '../src/jsonrpc-server.php';
 
 class Calculator {
 	public function add($a, $b) {
 		return $a+$b;
 	}
 }
 
 $server = new HttpJsonRpcServer();
 $server->log = true;
 $server->register(new Calculator(), 'calc');
 $server->httpExec();
 