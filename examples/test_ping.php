<?php
require __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\RakNetClient;

$host = $argv[1] ?? '127.0.0.1';
$port = isset($argv[2]) ? (int) $argv[2] : 19132;

$client = new RakNetClient($host, $port);
$res = $client->ping();
if ($res === null) {
    echo "No response from {$host}:{$port}\n";
    exit(2);
}

echo "Decoded response:\n";
print_r($res);

$players = $res['players'] ?? '?';
$max = $res['maxPlayers'] ?? '?';
echo "Players: {$players}/{$max}\n";

$client->close();