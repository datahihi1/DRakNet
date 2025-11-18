<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\Utils\BinaryStream;
use Datahihi1\RakNet\RakNet;
use function chr;
use function intval;
use function ord;
use function strlen;

/**
 * Thông báo Connected Pong (phản hồi cho Connected Ping)
 */
class ConnectedPong extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_CONNECTED_PONG;
    }

    public static function encode(mixed $payload = null): string
    {
        $ts = $payload['timestamp'] ?? intval(microtime(true) * 1000);
        $serverGuid = $payload['server_guid'] ?? 0;
        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(pack('P', $ts));
        $bs->write(pack('P', (int)$serverGuid));
        return $bs->getBuffer();
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1 + 8 + 8) return null;
        $off = 0;
        $pid = ord($data[$off++]); if ($pid !== self::id()) return null;
        $ts = unpack('P', substr($data, $off, 8))[1]; $off += 8;
        $guid = unpack('P', substr($data, $off, 8))[1] ?? 0; $off += 8;
        return ['timestamp' => $ts, 'server_guid' => $guid];
    }
}