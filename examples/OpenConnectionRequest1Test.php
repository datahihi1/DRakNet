<?php
require __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\Protocol\OpenConnectionRequest1;

$packet = OpenConnectionRequest1::encode(protocol: 844);
echo "OpenConnectionRequest1 hex:\n";
echo bin2hex($packet) . PHP_EOL;