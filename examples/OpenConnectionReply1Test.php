<?php
require __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\Protocol\OpenConnectionRequest1;
use Datahihi1\RakNet\Protocol\OpenConnectionReply1;

$server = 'play.cubecraft.net'; // Replace with your server address
$port = 19132; // Replace with your server port

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 3, "usec" => 0]);

// Gửi OpenConnectionRequest1
$req = OpenConnectionRequest1::encode(11);
socket_sendto($socket, $req, strlen($req), 0, $server, $port);

$buf = '';
$from = '';
$bytes = @socket_recvfrom($socket, $buf, 4096, 0, $from, $port);

if ($bytes > 0 && ord($buf[0]) === 0x06) {
    $reply = OpenConnectionReply1::decode($buf);
    var_dump($reply);
} else {
    echo "Không nhận được phản hồi từ server.\n";
}
