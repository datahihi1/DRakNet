<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Utils;

use function ord;
use function strlen;

/**
 * Hỗ trợ luồng nhị phân để đọc/ghi dữ liệu thô.
 */
class BinaryStream
{
    /**
     * Bộ đệm nhị phân
     */
    private string $buffer = '';
    /**
     * Vị trí hiện tại trong bộ đệm
     */
    private int $offset = 0;

    /**
     * @param string $data Dữ liệu ban đầu (mặc định là chuỗi rỗng)
     */
    public function __construct(string $data = '')
    {
        $this->buffer = $data;
    }

    /**
     * Viết dữ liệu thô vào bộ đệm
     * @param string $data
     */
    public function write(string $data): void
    {
        $this->buffer .= $data;
    }

    /**
     * Viết số nguyên 64-bit vào bộ đệm
     * @param int $v
     */
    public function writeInt64(int $v): void
    {
        // 'P' là định dạng cho số nguyên 64-bit theo thứ tự byte của máy chủ
        $this->write(pack('P', $v));
    }

    /**
     * Viết số nguyên 32-bit vào bộ đệm
     * @param int $v
     */
    public function writeInt32(int $v): void
    {
        $this->write(pack('V', $v));
    }

    /**
     * Lấy nội dung bộ đệm hiện tại
     * @return string
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Đọc số nguyên 8-bit từ bộ đệm
     * @return int
     */
    public function readInt8(): int
    {
        $v = ord($this->buffer[$this->offset]);
        $this->offset++;
        return $v;
    }

    /**
     * Đọc dữ liệu thô từ bộ đệm
     * @param int $len
     * @return string
     */
    public function readBytes(int $len): string
    {
        $s = substr($this->buffer, $this->offset, $len);
        $this->offset += $len;
        return $s;
    }

    /**
     * Lấy số byte chưa đọc còn lại trong bộ đệm
     * @return int
     */
    public function remaining(): int
    {
        return strlen($this->buffer) - $this->offset;
    }
}
