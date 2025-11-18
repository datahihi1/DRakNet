<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Thông báo kết nối mới đến
 */
class NewIncomingConnection extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_NEW_INCOMING_CONNECTION;
    }

    public static function encode(mixed $payload = null): string
    {
        return chr(self::id());
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1) return null;
        $pid = ord($data[0]); if ($pid !== self::id()) return null;
        return [];
    }
}