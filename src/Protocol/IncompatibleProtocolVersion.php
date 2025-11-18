<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Gói thông báo phiên bản giao thức không tương thích
 */
class IncompatibleProtocolVersion extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_INCOMPATIBLE_PROTOCOL_VERSION;
    }

    /**
     * Mã hóa gói (server gửi)
     *
     * payload keys:
     *  - server_guid (int|64bit)
     *  - client_protocol (int)
     *  - server_protocol (int)
     *
     * Trả về chuỗi byte đã mã hóa.
     */
    public static function encode(mixed $payload = null): string
    {
        $serverGuid = $payload['server_guid'] ?? 0;
        $clientProtocol = $payload['client_protocol'] ?? 0;
        $serverProtocol = $payload['server_protocol'] ?? 0;

        $buf = '';
        $buf .= chr(self::id());
        $buf .= pack('P', (int)$serverGuid);
        // Use 32-bit little-endian for protocol versions (best-effort)
        $buf .= pack('V', (int)$clientProtocol);
        $buf .= pack('V', (int)$serverProtocol);

        return $buf;
    }

    /**
     * Giải mã gói (client nhận)
     *
     * Trả về mảng thông tin hoặc null nếu không hợp lệ.
     */
    public static function decode(string $data): ?array
    {
        if (strlen($data) < 1 + 8) {
            return null;
        }

        $off = 0;
        $pid = ord($data[$off++]);
        if ($pid !== self::id()) {
            return null;
        }

        $serverGuid = @unpack('P', substr($data, $off, 8))[1] ?? 0;
        $off += 8;

        $remaining = strlen($data) - $off;
        $clientProtocol = 0;
        $serverProtocol = 0;

        // Prefer 4-byte little-endian ints if present, else try 1-byte values as fallback
        if ($remaining >= 8) {
            $clientProtocol = @unpack('V', substr($data, $off, 4))[1] ?? 0;
            $off += 4;
            $serverProtocol = @unpack('V', substr($data, $off, 4))[1] ?? 0;
        } elseif ($remaining >= 2) {
            $clientProtocol = ord($data[$off++]);
            $serverProtocol = ord($data[$off++]);
        } elseif ($remaining === 1) {
            $clientProtocol = ord($data[$off++]);
        }

        return [
            'server_guid' => $serverGuid,
            'client_protocol' => (int)$clientProtocol,
            'server_protocol' => (int)$serverProtocol,
        ];
    }
}