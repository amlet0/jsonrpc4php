# jsonrpc4php

JSONRPC implementation in PHP, compliant with http://www.jsonrpc.org/specification

Features:
- JsonRpc Server (transport protocol agnostic)
- HTTP JsonRpc Server
- support for single request, batch request, notifications

JsonRpc client is missing (do you need it? help me!)

# USAGE
* steps:
    + import jsonrpc-server.php in your project
    + define an object with rpc-exported methods

example:

* * *
*file: rpc.php*

    <?php 
    require_once 'jsonrpc-server.php';
    
    class Calculator {
        public function add($a, $b) {
        return $a+$b;
        }
    }
 
    $server = new HttpJsonRpcServer();
    $server->register(new Calculator(), 'calc');
    $server->httpExec(); 
    ?> 

* * *
*file: index.html*
    <html>
    
    <head>
    <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
    <script>
    $(function() {
        // rpc call: calc.add(1, 2)
        $.post(
            'rpc.php',
            JSON.stringify({ jsonrpc: '2.0', method: 'calc.add', params: [1, 2], id: "1" }),
            null, 'json'
        )
        .done(function(data) {
            alert(data.result);
        });
    });
    </script>
    </head>
    
    <body>
    </body>
    
    </html>

