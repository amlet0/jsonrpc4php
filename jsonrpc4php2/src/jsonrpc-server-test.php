<?php
require_once 'jsonrpc-server.php';

class Test1 {

 public function add($a, $b) {
 	return $a+$b;
 }
 
 public function nop() {
 }
	
}

class Test2 {
	public function sub($a, $b) {
		return $a-$b;
	}
}

// TODO implement true test case via assertion

$server = new JsonRpcServer();
$server->log=true;
$server->register(new Test1());
$server->register(new Test2(), 'test');

// rpc call with positional parameters
$request = '{"jsonrpc": "2.0", "method": "add", "params": [1, 3], "id": 1}';
$response = $server->exec($request);

// rpc call with named parameters
$request = '{"jsonrpc": "2.0", "method": "test.sub", "params": {"a": 12, "b": 10}, "id":2}';
$response = $server->exec($request);

// a notification
$request = '{"jsonrpc": "2.0", "method": "add", "params": [1, 3] }';
$response = $server->exec($request);

// rpc call of non-existent method
$request = '{"jsonrpc": "2.0", "method": "test.sub", "params": [1, 3], "id":3 }';
$response = $server->exec($request);

// rpc call with invalid JSON
$request = '{"jsonrpc": "2.0", "method": "foobar, "params": "bar", "baz]';
$response = $server->exec($request);

// rpc call with invalid Request object
$request = '{"jsonrpc": "2.0", "method": 1, "params": "bar"}';
$response = $server->exec($request);

// rpc call Batch, invalid JSON
$request = <<<EOD
[
{"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
{"jsonrpc": "2.0", "method"
]
EOD;
$response = $server->exec($request);

//rpc call with an empty Array
$request = '[]';
$response = $server->exec($request);

// rpc call with an invalid Batch (but not empty):
$request = '[1]';
$response = $server->exec($request);

// rpc call with invalid Batch
$request = '[1,2,3]';
$response = $server->exec($request);

// rpc call Batch
$request = <<<EOD
[
{"jsonrpc": "2.0", "method": "add", "params": [1,2], "id": "1"},
{"jsonrpc": "2.0", "method": "add", "params": [7, 3]},
{"jsonrpc": "2.0", "method": "add", "params": [42,23], "id": "2"},
{"foo": "boo"},
{"jsonrpc": "2.0", "method": "nofunction", "params": {"name": "myself"}, "id": "5"},
{"jsonrpc": "2.0", "method": "nop", "id": "9"}
]
EOD;
$response = $server->exec($request);

// rpc call Batch (all notifications)
$request = <<<EOD
[
{"jsonrpc": "2.0", "method": "add", "params": [2, 3]},
{"jsonrpc": "2.0", "method": "add", "params": [6, 4]}
]
EOD;
$response = $server->exec($request);

