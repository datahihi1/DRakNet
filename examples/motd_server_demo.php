<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\RakNetServer;

// Demo MOTD server
$motd = 'MCPE;Demo MOTD Server;2;1.20.0;0;20;1234567890';
$server = new RakNetServer($motd, 19132);
$server->run();
