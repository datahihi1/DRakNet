<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use Datahihi1\RakNet\Utils\BinaryStream;
use function chr;
use function ord;
use function strlen;

/**
 * Yêu cầu kết nối
 * (client -> server) tối thiểu
 */
class ConnectionRequest extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_CONNECTION_REQUEST;
    }

    public static function encode(mixed $payload = null): string
    {
        $clientGuid = $payload['client_guid'] ?? 0;
        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(pack('P', $clientGuid));
        // optional payload
        if (isset($payload['data'])) $bs->write($payload['data']);
        return $bs->getBuffer();
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1 + 8) return null;
        $off = 0;
        $pid = ord($data[$off++]); if ($pid !== self::id()) return null;
        $clientGuid = unpack('P', substr($data, $off, 8))[1]; $off += 8;
        $rest = substr($data, $off);
        return ['client_guid' => $clientGuid, 'data' => $rest];
    }
}
