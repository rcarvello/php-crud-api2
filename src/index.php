<?php
use Com\Tqdev\CrudApi\Api;
use Com\Tqdev\CrudApi\Config;
use Com\Tqdev\CrudApi\Request;

// do not reformat the following line
spl_autoload_register(function ($class) {include str_replace('\\', '/', __DIR__ . "/$class.php");});
// as it is excluded in the build

$config = new Config([
    'username' => 'php-crud-api',
    'password' => 'php-crud-api',
    'database' => 'php-crud-api',
    'cacheType' => 'Redis',
    //'cachePath' => 'tmp',
    //    'debug' => true,
]);
//$request = new Request('GET', '/meta/columns');
$request = new Request();
//$request->addHeader('Origin');
$api = new Api($config);
$response = $api->handle($request);
//echo $response;
$response->output();
