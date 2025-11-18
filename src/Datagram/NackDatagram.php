<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use function chr;

/**
 * NACK Datagram - Báo cáo các datagrams bị thiếu
 * 
 * Structure:
 * - Flags (1 byte): reserved
 * - RangeList (NACK ranges)
 */
class NackDatagram
{
    /** RangeList chứa các sequence numbers bị thiếu */
    public RangeList $nackRanges;
    
    public function __construct()
    {
        $this->nackRanges = new RangeList();
    }
    
    /**
     * Mã hóa NACK Datagram thành bytes
     * 
     * @return string
     */
    public function encode(): string
    {
        $buf = '';
        
        // Flags byte (tất cả reserved, is_valid=0, is_nack=1 được set ở lớp trên)
        $buf .= chr(0x00);
        
        // RangeList
        $buf .= $this->nackRanges->encode();
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành NackDatagram
     * 
     * @param string $data
     * @param int $offset
     * @return array [NackDatagram, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $datagram = new self();
        
        // Flags (bỏ qua, không sử dụng)
        $offset++;
        
        // RangeList
        [$rangeList, $offset] = RangeList::decode($data, $offset);
        $datagram->nackRanges = $rangeList;
        
        return [$datagram, $offset];
    }
    
    /**
     * Thêm sequence number vào NACK ranges
     * 
     * @param int $sequenceNumber
     */
    public function addNack(int $sequenceNumber): void
    {
        $this->nackRanges->add($sequenceNumber);
    }
}

