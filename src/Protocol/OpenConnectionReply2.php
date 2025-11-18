<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use Datahihi1\RakNet\Utils\BinaryStream;
use function chr;
use function ord;
use function strlen;

/**
 * Mã gói trả lời kết nối mở RakNet 2
 *
 * Gói được máy chủ gửi để phản hồi yêu cầu Mở Kết Nối 2 từ phía máy khách.
 */
class OpenConnectionReply2 extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_OPEN_CONNECTION_REPLY_2;
    }

    /**
     * Mã hóa Gói trả lời kết nối mở 2 (server gửi)
     */
    public static function encode(mixed $payload = null): string
    {
        $serverGuid = $payload['server_guid'] ?? 0;
        $clientAddress = $payload['client_address'] ?? '127.0.0.1';
        $clientPort = $payload['client_port'] ?? 0;
        $mtuSize = $payload['mtu_size'] ?? 1492;

        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(RakNet::magicBytes());

        // Server GUID
        $bs->write(pack('P', $serverGuid));

        // Client address
        $ipParts = array_map('intval', explode('.', $clientAddress));
        foreach ($ipParts as $part) {
            $bs->write(chr($part));
        }
        $bs->write(pack('n', $clientPort));

        // MTU size
        $bs->write(pack('n', $mtuSize));

        // Encryption (0 = disabled)
        $bs->write(chr(0x00));

        return $bs->getBuffer();
    }

    /**
     * Giải mã Gói trả lời kết nối mở 2
     */
    public static function decode(string $data): mixed
    {
        if (strlen($data) < 1 + 16 + 8)
            return null;

        $off = 0;
        $pid = ord($data[$off++]);
        if ($pid !== self::id())
            return null;

        $magic = substr($data, $off, 16);
        $off += 16;
        if ($magic !== RakNet::magicBytes())
            return null;

        $serverGuid = unpack('P', substr($data, $off, 8))[1];
        $off += 8;

        $remaining = substr($data, $off);

        // Best-effort: if server included extra data (e.g. encryption confirmation), expose it
        return [
            'server_guid' => $serverGuid,
            'raw' => $remaining,
            'encryption' => (strlen($remaining) > 0), // heuristic
        ];
    }
}