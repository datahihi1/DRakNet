<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use function chr;
use function count;
use function ord;

/**
 * Range structure cho ACK/NACK
 * 
 * Đại diện cho một range của sequence numbers
 */
class Range
{
    /** Giá trị nhỏ nhất trong range */
    public int $min;
    
    /** Giá trị lớn nhất trong range */
    public int $max;
    
    /**
     * @param int $min
     * @param int $max
     */
    public function __construct(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;
    }
    
    /**
     * Kiểm tra xem range có phải là single (min == max) không
     */
    public function isSingle(): bool
    {
        return $this->min === $this->max;
    }
    
    /**
     * Mã hóa range thành bytes
     * 
     * Format:
     * - is_single (bool, 1 byte)
     * - min (uint24, little endian, 3 bytes)
     * - max (uint24, little endian, 3 bytes) - chỉ có nếu !is_single
     */
    public function encode(): string
    {
        $buf = '';
        $isSingle = $this->isSingle();
        $buf .= chr($isSingle ? 1 : 0);
        
        // min (uint24, little endian)
        $buf .= pack('V', $this->min) & "\xFF\xFF\xFF";
        
        // max chỉ có nếu không phải single
        if (!$isSingle) {
            $buf .= pack('V', $this->max) & "\xFF\xFF\xFF";
        }
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành Range
     * 
     * @param string $data
     * @param int $offset
     * @return array [Range, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $isSingle = ord($data[$offset++]) === 1;
        
        // min (uint24, little endian)
        $minBytes = substr($data, $offset, 3) . "\x00";
        $min = unpack('V', $minBytes)[1] & 0xFFFFFF;
        $offset += 3;
        
        $max = $min;
        if (!$isSingle) {
            // max (uint24, little endian)
            $maxBytes = substr($data, $offset, 3) . "\x00";
            $max = unpack('V', $maxBytes)[1] & 0xFFFFFF;
            $offset += 3;
        }
        
        return [new self($min, $max), $offset];
    }
    
    /**
     * Merge các range liên tiếp để tối ưu hóa
     * 
     * @param Range[] $ranges
     * @return Range[]
     */
    public static function mergeRanges(array $ranges): array
    {
        if (empty($ranges)) {
            return [];
        }
        
        // Sắp xếp theo min
        usort($ranges, fn($a, $b) => $a->min <=> $b->min);
        
        $merged = [];
        $current = $ranges[0];
        
        for ($i = 1; $i < count($ranges); $i++) {
            $next = $ranges[$i];
            
            // Nếu range tiếp theo liên tiếp hoặc overlap
            if ($next->min <= $current->max + 1) {
                $current->max = max($current->max, $next->max);
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }
        
        $merged[] = $current;
        return $merged;
    }
}

