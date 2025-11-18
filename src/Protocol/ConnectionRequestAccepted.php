<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Thông báo chấp nhận yêu cầu kết nối
 * (server -> client) tối thiểu
 */
class ConnectionRequestAccepted extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_CONNECTION_REQUEST_ACCEPTED;
    }

    public static function encode(mixed $payload = null): string
    {
        $buf = chr(self::id());
        if (isset($payload['server_guid'])) $buf .= pack('P', (int)$payload['server_guid']);
        if (isset($payload['data'])) $buf .= $payload['data'];
        return $buf;
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1 + 8) return null;
        $off = 0;
        $pid = ord($data[$off++]); if ($pid !== self::id()) return null;
        $serverGuid = unpack('P', substr($data, $off, 8))[1]; $off += 8;
        $rest = substr($data, $off);
        return ['server_guid' => $serverGuid, 'data' => $rest];
    }
}