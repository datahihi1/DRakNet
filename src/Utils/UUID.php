<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Utils;

use function chr;
use function ord;

/**
 * Tiện ích UUID và GUID
 */
class UUID
{
    /**
     * Tạo GUID 64-bit ngẫu nhiên
     */
    public static function generateGUID64(): int
    {
        // Tạo số nguyên 64-bit ngẫu nhiên
        $high = random_int(0, 0xFFFFFFFF);
        $low = random_int(0, 0xFFFFFFFF);
        // Kết hợp hai phần để tạo GUID 64-bit
        return ($high << 32) | $low;
    }

    /**
     * Tạo UUID ngẫu nhiên (chuỗi 36 ký tự chuẩn)
     */
    public static function v4(): string
    {
        // Tạo 16 byte ngẫu nhiên
        $data = random_bytes(16);
        // Đặt phiên bản UUID (4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Đặt biến thể UUID (RFC 4122)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        // Trả về chuỗi định dạng UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
