<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use Datahihi1\RakNet\Utils\BinaryStream;
use function chr;
use function intval;

/**
 * Mã gói Unconnected Ping RakNet
 *
 * Gói được khách hàng gửi để ping máy chủ.
 */
class UnconnectedPing extends Packet
{
    /**
     * Lấy ID gói
     */
    public static function id(): int
    {
        return RakNet::ID_UNCONNECTED_PING;
    }

    /**
     * Mã hóa gói thành byte thô
     * @param mixed $payload Ngày dấu thời gian tùy chọn tính bằng mili giây (int). Nếu null, sử dụng thời gian hiện tại.
     * @return string
     */
    public static function encode(mixed $payload = null): string
    {
        $ts = $payload ?? intval(microtime(true) * 1000);
        $bs = new BinaryStream();
        $bs->write(chr(self::id()));


        // Thời gian dấu (8 byte, little endian)
        $bs->write(pack('P', $ts));


        // Mã ma thuật RakNet (byte cố định theo giao thức)
        $magic = hex2bin('00ffff00fefefefefdfdfdfd12345678');
        $bs->write($magic);


        // GUID máy chủ (8 byte, đặt thành 0 cho ping không kết nối)
        $bs->write(pack('P', 0));

        // Trả về bộ đệm
        return $bs->getBuffer();
    }

    /**
     * Giải mã byte thô thành gói (trợ giúp tĩnh)
     * @param string $data
     * @return null
     */
    public static function decode(string $data): mixed
    {
        // Không cần giải mã cho Unconnected Ping
        return null;
    }
}
