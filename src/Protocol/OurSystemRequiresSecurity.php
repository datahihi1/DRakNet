<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Hệ thống của người dùng yêu cầu bảo mật
 */
class OurSystemRequiresSecurity extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_OUR_SYSTEM_REQUIRES_SECURITY;
    }

    public static function encode(mixed $payload = null): string
    {
        $buf = chr(self::id());
        if (isset($payload['data'])) $buf .= $payload['data'];
        return $buf;
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1) return null;
        $pid = ord($data[0]);
        if ($pid !== self::id()) return null;
        return ['data' => substr($data, 1)];
    }
}