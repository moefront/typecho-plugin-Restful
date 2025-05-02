<?php
use MoeFront\RestfulTests\Util;
use SSX\Utility\Serve;

if (php_sapi_name() !== 'cli') {
    exit;
}

// Errors on full!
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

Util::downloadTypecho();

// Start build-in server
$server[] = new Serve(array(
    'address' => getenv('WEB_SERVER_HOST'),
    'port' => getenv('WEB_SERVER_PORT'),
    'document_root' => getenv('WEB_SERVER_DOCROOT'),
));
$server[] = new Serve(array(
    'address' => getenv('WEB_SERVER_HOST'),
    'port' => getenv('FORKED_WEB_SERVER_PORT'),
    'document_root' => getenv('WEB_SERVER_DOCROOT'),
));

$server[0]->start();
$server[1]->start();

// Kill the web server when the process ends
register_shutdown_function(function () use ($server) {
    $server[0]->stop();
    $server[1]->stop();
});

Util::installTypecho();
