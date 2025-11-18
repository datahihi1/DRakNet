<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\Utils\BinaryStream;
use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Mã gói trả lời kết nối mở RakNet 1
 *
 * Gói được máy chủ gửi để phản hồi yêu cầu Mở Kết Nối 1 từ phía máy khách.
 */
class OpenConnectionReply1 extends Packet
{
    /**
     * Lấy ID gói (Open Connection Reply 1)
     */
    public static function id(): int
    {
        return RakNet::ID_OPEN_CONNECTION_REPLY_1;
    }

    /**
     * Mã hóa Gói trả lời kết nối mở 1 (server gửi)
     */
    public static function encode(mixed $payload = null): string
    {
        $guid = $payload['server_guid'] ?? 0;
        $mtu = $payload['mtu_size'] ?? 1492;
        $serverSecurity = $payload['server_security'] ?? 0;
        $publicKey = $payload['public_key'] ?? null;

        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(RakNet::magicBytes());
        $bs->write(pack('P', $guid));
        $bs->write(chr($serverSecurity ? 0x01 : 0x00));

        if ($serverSecurity && is_string($publicKey)) {
            // write public key length (unsigned short big endian) + key bytes
            $bs->write(pack('n', strlen($publicKey)));
            $bs->write($publicKey);
        } else {
            $bs->write(str_repeat("\x00", $mtu - 28)); // padding để match MTU test
        }

        return $bs->getBuffer();
    }

    /**
     * Giải mã Gói trả lời kết nối mở 1
     * 
     * @param string $data Raw data received from socket
     * @return array|null Information array or null if invalid
     */
    public static function decode(string $data): ?array
    {
        if (strlen($data) < 1 + 16 + 8 + 1) {
            return null; // minimum 26 bytes
        }

        $off = 0;
        $pid = ord($data[$off++]);
        if ($pid !== self::id()) {
            return null;
        }

        $magic = substr($data, $off, 16);
        $off += 16;

        // Magic check
        if ($magic !== RakNet::magicBytes()) {
            return null;
        }

        $serverGuid = unpack('P', substr($data, $off, 8))[1];
        $off += 8;

        $serverSecurity = ord($data[$off++]); // thường là 0

        $result = [
            'server_guid' => $serverGuid,
            'server_security' => (bool) $serverSecurity,
        ];

        // If server indicates security, attempt to read public key length + key (if present)
        if ($serverSecurity) {
            if (strlen($data) >= $off + 2) {
                $keyLen = unpack('n', substr($data, $off, 2))[1];
                $off += 2;
                if ($keyLen > 0 && strlen($data) >= $off + $keyLen) {
                    $pub = substr($data, $off, $keyLen);
                    $result['server_public_key'] = $pub; // raw bytes
                    $off += $keyLen;
                }
            }
            // try to estimate MTU if remaining padding present
            $result['mtu_size'] = strlen($data) - $off + 28;
        } else {
            // estimate MTU (RakNet subtracts 28 bytes header)
            $result['mtu_size'] = strlen($data) - $off + 28;
        }

        return $result;
    }
}
