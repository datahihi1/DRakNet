<?php
require __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\RakNet;
use Datahihi1\RakNet\Utils\UUID;
use Datahihi1\RakNet\Protocol\UnconnectedPing;
use Datahihi1\RakNet\Protocol\UnconnectedPong;
use Datahihi1\RakNet\Protocol\OpenConnectionRequest1;
use Datahihi1\RakNet\Protocol\OpenConnectionReply1;
use Datahihi1\RakNet\Protocol\OpenConnectionRequest2;
use Datahihi1\RakNet\Protocol\OpenConnectionReply2;
use Datahihi1\RakNet\Utils\Crypto; // <-- thêm

$host = $argv[1] ?? '127.0.0.1';
$port = isset($argv[2]) ? (int) $argv[2] : 19132;
$timeoutSec = 3;

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ["sec" => $timeoutSec, "usec" => 0]);

$recv = function () use ($sock, &$host, &$port) {
    $buf = '';
    $from = '';
    $p = 0;
    $bytes = @socket_recvfrom($sock, $buf, 4096, 0, $from, $p);
    if ($bytes === false || $bytes === 0)
        return null;
    return $buf;
};

echo "1) Send UnconnectedPing\n";
socket_sendto($sock, UnconnectedPing::encode(), strlen(UnconnectedPing::encode()), 0, $host, $port);
$raw = $recv();
if ($raw === null) {
    echo "No UnconnectedPong received\n";
    goto CLEAN;
}
echo "UnconnectedPong raw: " . bin2hex($raw) . PHP_EOL;
$pong = UnconnectedPong::decode($raw);
var_export($pong);
echo PHP_EOL;

echo "2) Send OpenConnectionRequest1\n";
socket_sendto($sock, OpenConnectionRequest1::encode(), strlen(OpenConnectionRequest1::encode()), 0, $host, $port);
$raw = $recv();
if ($raw === null) {
    echo "No OpenConnectionReply1 received\n";
    goto CLEAN;
}
echo "OpenConnectionReply1 raw: " . bin2hex($raw) . PHP_EOL;
$reply1 = OpenConnectionReply1::decode($raw);
var_export($reply1);
echo PHP_EOL;

if (!is_array($reply1) || empty($reply1['mtu_size'])) {
    echo "Invalid Reply1, abort\n";
    goto CLEAN;
}

$mtu = (int) $reply1['mtu_size'];
$clientGuid = UUID::generateGUID64();

if (!empty($reply1['server_security'])) {
    echo "Server requires security. Attempting secure handshake (best-effort)...\n";

    if (empty($reply1['server_public_key'])) {
        echo "No server public key present in Reply1 — cannot perform proper secure handshake.\n";
        echo "Attempting fallback: send OpenConnectionRequest2 without encryption blob to see if server responds.\n";

        // fallback: send unencrypted Request2 to test server behavior
        $req2 = OpenConnectionRequest2::encode([
            'server_address' => $host,
            'server_port' => $port,
            'mtu_size' => $mtu,
            'client_guid' => $clientGuid,
        ]);
        socket_sendto($sock, $req2, strlen($req2), 0, $host, $port);
        $raw = $recv();
        if ($raw === null) {
            echo "Fallback Request2 also produced no Reply2. Server likely enforces encryption or expects different handshake.\n";
            goto CLEAN;
        }
        echo "Received Reply2 (fallback): " . bin2hex($raw) . PHP_EOL;
        $reply2 = OpenConnectionReply2::decode($raw);
        var_export($reply2);
        echo PHP_EOL;
        goto CLEAN;
    }

    // Create 16-byte AES session key
    $sessionKey = random_bytes(16);

    // Pack client GUID + sessionKey as client secret blob (server implementations vary)
    $clientSecret = pack('P', $clientGuid) . $sessionKey;

    $serverPubRaw = $reply1['server_public_key'];
    $serverPem = null;
    if (str_starts_with($serverPubRaw, "-----BEGIN")) {
        $serverPem = $serverPubRaw;
    } else {
        // assume DER -> convert to PEM base64
        $b64 = chunk_split(base64_encode($serverPubRaw), 64, "\n");
        $serverPem = "-----BEGIN PUBLIC KEY-----\n{$b64}-----END PUBLIC KEY-----\n";
    }

    $encrypted = null;
    $ok = @openssl_public_encrypt($clientSecret, $encrypted, $serverPem, OPENSSL_PKCS1_OAEP_PADDING);
    if ($ok !== true) {
        echo "openssl_public_encrypt failed — secure handshake cannot proceed.\n";
        goto CLEAN;
    }

    $req2 = OpenConnectionRequest2::encode([
        'server_address' => $host,
        'server_port' => $port,
        'mtu_size' => $mtu,
        'client_guid' => $clientGuid,
        'encryption' => $encrypted,
    ]);
    socket_sendto($sock, $req2, strlen($req2), 0, $host, $port);

    $raw = $recv();
    if ($raw === null) {
        echo "No OpenConnectionReply2 received (server may expect different secure handshake).\n";
        goto CLEAN;
    }
    echo "OpenConnectionReply2 raw: " . bin2hex($raw) . PHP_EOL;
    $reply2 = OpenConnectionReply2::decode($raw);
    var_export($reply2);
    echo PHP_EOL;

    // If server indicates encryption enabled in Reply2, enable Crypto with sessionKey
    if (!empty($reply2['encryption'])) {
        echo "Server acknowledged encryption, initializing session AES.\n";
        try {
            $crypto = new Crypto($sessionKey);

            // Demo: encrypt and decrypt a sample application payload
            $demoPayload = "HELLO_FROM_CLIENT";
            $encPayload = $crypto->encrypt($demoPayload);
            echo "Demo encrypted payload (hex): " . bin2hex($encPayload) . PHP_EOL;

            // In real usage you would send $encPayload as packet body from now on.
            // For demonstration, decrypt locally:
            $dec = $crypto->decrypt($encPayload);
            echo "Demo decrypted payload: " . $dec . PHP_EOL;
        } catch (\Throwable $e) {
            echo "Crypto init failed: " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "Server did not enable encryption despite requesting security earlier.\n";
    }

    goto CLEAN;
}

// non-secure flow
echo "3) Send OpenConnectionRequest2 (mtu={$mtu})\n";
$req2 = OpenConnectionRequest2::encode([
    'server_address' => $host,
    'server_port' => $port,
    'mtu_size' => $mtu,
    'client_guid' => $clientGuid,
]);
socket_sendto($sock, $req2, strlen($req2), 0, $host, $port);
$raw = $recv();
if ($raw === null) {
    echo "No OpenConnectionReply2 received\n";
    goto CLEAN;
}
echo "OpenConnectionReply2 raw: " . bin2hex($raw) . PHP_EOL;
$reply2 = OpenConnectionReply2::decode($raw);
var_export($reply2);
echo PHP_EOL;

CLEAN:
socket_close($sock);