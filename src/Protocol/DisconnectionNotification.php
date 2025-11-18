<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Thông báo ngắt kết nối
 */
class DisconnectionNotification extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_DISCONNECTION_NOTIFICATION;
    }

    public static function encode(mixed $payload = null): string
    {
        $buf = chr(self::id());
        if (isset($payload['reason'])) $buf .= $payload['reason'];
        return $buf;
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1) return null;
        if (ord($data[0]) !== self::id()) return null;
        return ['reason' => substr($data, 1)];
    }
}