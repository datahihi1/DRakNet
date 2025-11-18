<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Server yêu cầu client cung cấp public key (minimal representation)
 */
class RemoteSystemRequiresPublicKey extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_REMOTE_SYSTEM_REQUIRES_PUBLIC_KEY;
    }

    public static function encode(mixed $payload = null): string
    {
        $buf = chr(self::id());
        // optional reason / data
        if (isset($payload['data'])) $buf .= $payload['data'];
        return $buf;
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1) return null;
        $pid = ord($data[0]);
        if ($pid !== self::id()) return null;
        $rest = substr($data, 1);
        return ['data' => $rest];
    }
}