<?php
require_once "../src/jsonrpc-client.php";

$proxy = new HttpJsonRpcClient('http://localhost:8080/jsonrpc-httpserver-test.php');
 
echo $proxy->calc->add(1, 2)."\n";
