<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use function count;
use function pack;
use function unpack;
use function substr;

/**
 * RangeList structure cho ACK/NACK
 * 
 * Chứa danh sách các Range đã được merge và tối ưu hóa
 */
class RangeList
{
    /** @var Range[] */
    private array $ranges = [];
    
    /**
     * Thêm một sequence number vào list
     * 
     * @param int $sequenceNumber
     */
    public function add(int $sequenceNumber): void
    {
        $this->ranges[] = new Range($sequenceNumber, $sequenceNumber);
        $this->optimize();
    }
    
    /**
     * Thêm một range vào list
     * 
     * @param Range $range
     */
    public function addRange(Range $range): void
    {
        $this->ranges[] = $range;
        $this->optimize();
    }
    
    /**
     * Tối ưu hóa ranges bằng cách merge các range liên tiếp
     */
    private function optimize(): void
    {
        $this->ranges = Range::mergeRanges($this->ranges);
    }
    
    /**
     * Lấy số lượng ranges
     */
    public function count(): int
    {
        return count($this->ranges);
    }
    
    /**
     * Lấy tất cả ranges
     * 
     * @return Range[]
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }
    
    /**
     * Kiểm tra xem sequence number có trong list không
     * 
     * @param int $sequenceNumber
     * @return bool
     */
    public function contains(int $sequenceNumber): bool
    {
        foreach ($this->ranges as $range) {
            if ($sequenceNumber >= $range->min && $sequenceNumber <= $range->max) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Mã hóa RangeList thành bytes
     * 
     * Format:
     * - size (uint16, big endian, 2 bytes)
     * - ranges[] (Range[])
     */
    public function encode(): string
    {
        $buf = '';
        
        // size (uint16, big endian)
        $count = count($this->ranges);
        $buf .= pack('n', $count);
        
        // ranges
        foreach ($this->ranges as $range) {
            $buf .= $range->encode();
        }
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành RangeList
     * 
     * @param string $data
     * @param int $offset
     * @return array [RangeList, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $list = new self();
        
        // size (uint16, big endian)
        $size = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
        
        // ranges
        for ($i = 0; $i < $size; $i++) {
            [$range, $offset] = Range::decode($data, $offset);
            $list->addRange($range);
        }
        
        return [$list, $offset];
    }
    
    /**
     * Xóa tất cả ranges
     */
    public function clear(): void
    {
        $this->ranges = [];
    }
    
    /**
     * Kiểm tra xem list có rỗng không
     */
    public function isEmpty(): bool
    {
        return empty($this->ranges);
    }
}

