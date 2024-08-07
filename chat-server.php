<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Chat.php';
require __DIR__ . '/config.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($pdo)
        )
    ),
    8080
);

$server->run();
