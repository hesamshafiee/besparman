<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\MongoDBHandler;
use MongoDB\Client;

class MongoLogger
{
    public function __invoke(array $config)
    {
        $host = config('database.connections.mongodb.host');
        $port = config('database.connections.mongodb.port');
        $username = config('database.connections.mongodb.username');
        $password = config('database.connections.mongodb.password');
        $database = config('database.connections.mongodb.database');

        $uri = "mongodb://{$username}:{$password}@{$host}:{$port}";

        $client = new Client($uri);

        return new Logger('mongodb', [
            new MongoDBHandler($client, $database, 'logs'),
        ]);
    }
}
