<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\Utils\BinaryStream;
use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;
use function strlen;

/**
 * Mã gói yêu cầu kết nối mở RakNet 2
 *
 * Gói được khách hàng gửi để yêu cầu kết nối đến máy chủ (bước thứ hai).
 */
class OpenConnectionRequest2 extends Packet
{
    public static function id(): int
    {
        return RakNet::ID_OPEN_CONNECTION_REQUEST_2;
    }

    /**
     * Encode (client gửi)
     *
     * payload keys:
     *  - server_address (string)
     *  - server_port (int)
     *  - mtu_size (int)
     *  - client_guid (int|64bit)
     *  - encryption (optional raw bytes) -- client encryption blob to send when server requests security
     */
    public static function encode(mixed $payload = null): string
    {
        $serverAddress = $payload['server_address'] ?? '0.0.0.0';
        $serverPort = $payload['server_port'] ?? 19132;
        $mtuSize = $payload['mtu_size'] ?? 1492;
        $clientGuid = $payload['client_guid'] ?? 0;
        $encryptionBlob = $payload['encryption'] ?? null;

        $bs = new BinaryStream();
        $bs->write(chr(self::id()));
        $bs->write(RakNet::magicBytes());

        // Server address (IPv4)
        $ipParts = array_map('intval', explode('.', $serverAddress));
        for ($i = 0; $i < 4; $i++) {
            $bs->write(chr($ipParts[$i] ?? 0));
        }
        $bs->write(pack('n', $serverPort)); // unsigned short, big-endian

        // MTU size
        $bs->write(pack('n', $mtuSize));

        // Client GUID
        $bs->write(pack('P', $clientGuid));

        // If encryption blob provided, append length + blob (unsigned short + data)
        if (is_string($encryptionBlob) && strlen($encryptionBlob) > 0) {
            $bs->write(pack('n', strlen($encryptionBlob)));
            $bs->write($encryptionBlob);
        }

        return $bs->getBuffer();
    }

    /**
     * Decode (server nhận)
     */
    public static function decode(string $data): ?array
    {
        if (strlen($data) < 33)
            return null;

        $bs = new BinaryStream($data);
        if ($bs->readInt8() !== self::id())
            return null;

        $magic = $bs->readBytes(16);
        if ($magic !== RakNet::magicBytes())
            return null;

        $ip = sprintf(
            '%d.%d.%d.%d',
            ord($bs->readBytes(1)),
            ord($bs->readBytes(1)),
            ord($bs->readBytes(1)),
            ord($bs->readBytes(1))
        );
        $port = unpack('n', $bs->readBytes(2))[1];
        $mtuSize = unpack('n', $bs->readBytes(2))[1];
        $clientGuid = unpack('P', $bs->readBytes(8))[1];

        $enc = null;
        if ($bs->remaining() >= 2) {
            $len = unpack('n', $bs->readBytes(2))[1];
            if ($len > 0 && $bs->remaining() >= $len) {
                $enc = $bs->readBytes($len);
            }
        }

        return [
            'server_address' => $ip,
            'server_port' => $port,
            'mtu_size' => $mtuSize,
            'client_guid' => $clientGuid,
            'encryption_blob' => $enc,
        ];
    }
}