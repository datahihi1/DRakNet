<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Thông báo mất kết nối
 */
class ConnectionLost extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_CONNECTION_LOST;
    }

    public static function encode(mixed $payload = null): string
    {
        $buf = chr(self::id());
        if (!empty($payload['reason'])) $buf .= $payload['reason'];
        return $buf;
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1) return null;
        $pid = ord($data[0]); if ($pid !== self::id()) return null;
        return ['reason' => substr($data, 1)];
    }
}