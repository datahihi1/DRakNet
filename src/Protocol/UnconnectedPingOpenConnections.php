<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use Datahihi1\RakNet\Utils\BinaryStream;
use function chr;
use function intval;
use function ord;
use function strlen;

/**
 * Yêu cầu ping không kết nối với mở kết nối (wrapper đơn giản)
 */
class UnconnectedPingOpenConnections extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_UNCONNECTED_PING_OPEN_CONNECTIONS;
    }

    public static function encode(mixed $payload = null): string
    {
        $ts = $payload['timestamp'] ?? intval(microtime(true) * 1000);
        $guid = $payload['client_guid'] ?? 0;

        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(pack('P', $ts));
        $bs->write(RakNet::magicBytes());
        $bs->write(pack('P', $guid));
        return $bs->getBuffer();
    }

    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1 + 8 + 16 + 8) return null;
        $off = 0;
        $pid = ord($data[$off++]);
        if ($pid !== self::id()) return null;
        $ts = unpack('P', substr($data, $off, 8))[1]; $off += 8;
        $magic = substr($data, $off, 16); $off += 16;
        $guid = unpack('P', substr($data, $off, 8))[1] ?? 0;
        return ['timestamp' => $ts, 'magic' => $magic, 'client_guid' => $guid];
    }
}
