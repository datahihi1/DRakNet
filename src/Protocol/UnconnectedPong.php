<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

use Datahihi1\RakNet\RakNet;
use function ord;

/**
 * Mã gói RakNet Unconnected Pong
 *
 * Gói được máy chủ gửi để phản hồi lại một Unconnected Ping.
 */
class UnconnectedPong extends Packet
{
    /**
     * Lấy ID gói
     */
    public static function id(): int
    {
        return RakNet::ID_UNCONNECTED_PONG;
    }

    /**
     * Mã hóa gói thành byte thô
     * @param mixed $payload
     * @return string
     */
    public static function encode(mixed $payload = null): string
    {
        // Không cần mã hóa cho Unconnected Pong
        return '';
    }

    /**
     * Giải mã byte thô thành gói (trợ giúp tĩnh)
     * @param string $data
     * @return mixed
     */
    public static function decode(string $data): mixed
    {
        $off = 0;
        $id = ord($data[$off++]);
        if ($id !== self::id()) {
            return null;
        }

        // Dấu thời gian (8 byte, little endian)
        $ts = @unpack('P', substr($data, $off, 8))[1] ?? 0;
        $off += 8;

        // GUID máy chủ (8 byte)
        $guid = @unpack('P', substr($data, $off, 8))[1] ?? 0;
        $off += 8;

        // Mã ma thuật RakNet (16 byte)
        $magic = substr($data, $off, 16);
        $off += 16;

        // Nội dung MOTD (phần còn lại của gói)
        $motd = substr($data, $off);

        // Phân tích cú pháp "MCPE;..." trạng thái trường Bedrock
        $parts = explode(';', $motd);

        $result = [
            'timestamp' => $ts,
            'server_guid' => $guid,
            'motd' => $motd, // raw payload
        ];

        // Phần phân tích cú pháp bổ sung (nếu có)
        // Bố cục Bedrock phổ biến (nỗ lực tốt nhất):
        // 0: "MCPE"
        // 1: Tên hiển thị / MOTD
        // 2: giao thức
        // 3: tên phiên bản
        // 4: người chơi trực tuyến
        // 5: tối đa người chơi
        // 6: id duy nhất của máy chủ
        if (isset($parts[1]))
            $result['motd_text'] = $parts[1];
        if (isset($parts[2]))
            $result['protocol'] = $parts[2];
        if (isset($parts[3]))
            $result['version'] = $parts[3];
        if (isset($parts[4]))
            $result['players'] = (int) $parts[4];
        if (isset($parts[5]))
            $result['maxPlayers'] = (int) $parts[5];
        if (isset($parts[6]))
            $result['server_id'] = $parts[6];

        // Trả về kết quả phân tích cú pháp
        return $result;
    }
}
