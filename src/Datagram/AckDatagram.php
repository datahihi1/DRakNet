<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use function chr;
use function ord;
use function pack;
use function substr;

/**
 * ACK Datagram - Xác nhận đã nhận được datagrams
 * 
 * Structure:
 * - Flags (1 byte): requires_B_and_AS, reserved
 * - AS (float, 4 bytes) - chỉ có nếu requires_B_and_AS = true
 * - RangeList (ACK ranges)
 */
class AckDatagram
{
    /** Có yêu cầu B và AS không */
    public bool $requiresBandAS = false;
    
    /** AS value (float) - chỉ có nếu requiresBandAS = true */
    public ?float $as = null;
    
    /** RangeList chứa các sequence numbers đã ACK */
    public RangeList $ackRanges;
    
    public function __construct()
    {
        $this->ackRanges = new RangeList();
    }
    
    /**
     * Mã hóa ACK Datagram thành bytes
     * 
     * @return string
     */
    public function encode(): string
    {
        $buf = '';
        
        // Flags byte
        $flags = 0x00;
        if ($this->requiresBandAS) $flags |= 0x04;
        // Bit 1-2: reserved (is_valid=0, is_ack=1 được set ở lớp trên)
        // Bit 3-7: reserved
        $buf .= chr($flags);
        
        // AS (float) - chỉ có nếu requiresBandAS = true
        if ($this->requiresBandAS && $this->as !== null) {
            $buf .= pack('g', $this->as); // 'g' = float, little endian
        }
        
        // RangeList
        $buf .= $this->ackRanges->encode();
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành AckDatagram
     * 
     * @param string $data
     * @param int $offset
     * @return array [AckDatagram, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $datagram = new self();
        
        // Flags
        $flags = ord($data[$offset++]);
        $datagram->requiresBandAS = ($flags & 0x04) !== 0;
        
        // AS (float) - chỉ có nếu requiresBandAS = true
        if ($datagram->requiresBandAS) {
            $datagram->as = unpack('g', substr($data, $offset, 4))[1];
            $offset += 4;
        }
        
        // RangeList
        [$rangeList, $offset] = RangeList::decode($data, $offset);
        $datagram->ackRanges = $rangeList;
        
        return [$datagram, $offset];
    }
    
    /**
     * Thêm sequence number vào ACK ranges
     * 
     * @param int $sequenceNumber
     */
    public function addAck(int $sequenceNumber): void
    {
        $this->ackRanges->add($sequenceNumber);
    }
}

