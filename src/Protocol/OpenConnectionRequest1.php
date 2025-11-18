<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\Utils\BinaryStream;
use Datahihi1\RakNet\RakNet;
use function chr;
use function ord;

/**
 * Mã gói yêu cầu kết nối mở RakNet 1
 *
 * Gói được khách hàng gửi để khởi tạo kết nối đến máy chủ.
 */
class OpenConnectionRequest1 extends Packet
{
    /**
     * Lấy ID gói (Open Connection Request 1)
     */
    public static function id(): int
    {
        return RakNet::ID_OPEN_CONNECTION_REQUEST_1;
    }

    /**
     * Mã hóa Gói yêu cầu kết nối mở 1 (client gửi)
     * 
     * @param int $protocol RakNet protocol version
     */
    public static function encode(mixed $protocol = 11): string
    {
        $bs = new BinaryStream();
        $bs->write(chr(self::id()));

        // Magic bytes (RakNet identifier)
        $bs->write(hex2bin('00ffff00fefefefefdfdfdfd12345678'));

        // Protocol version (1 byte)
        $bs->write(chr($protocol));

        // Padding to reach minimum MTU size (46 bytes)
        $mtuPadding = str_repeat("\x00", 46);
        $bs->write($mtuPadding);

        // Return the encoded buffer
        return $bs->getBuffer();
    }

    /**
     * Giải mã Gói yêu cầu kết nối mở 1
     * 
     * @param string $data Raw data received from socket
     * @return array|null Information array or null if invalid
     */
    public static function decode(string $data): ?array
    {
        $id = ord($data[0]);
        if ($id !== self::id()) {
            return null;
        }

        $protocol = ord($data[1 + 16]); // after magic
        return ['protocol' => $protocol];
    }
}
