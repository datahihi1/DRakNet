<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use Datahihi1\RakNet\Capsule\DatagramCapsule;
use function chr;
use function ord;
use function pack;
use function strlen;
use function substr;

/**
 * Valid Datagram - Chứa các packet thực tế
 * 
 * Structure:
 * - Flags (1 byte): is_packet_pair, is_continuous_send, requires_B_and_AS, reserved
 * - Range number (uint24, little endian)
 * - Capsules[] (DatagramCapsule[])
 */
class ValidDatagram
{
    /** Có phải packet pair không */
    public bool $isPacketPair = false;
    
    /** Có phải continuous send không */
    public bool $isContinuousSend = false;
    
    /** Có yêu cầu B và AS không */
    public bool $requiresBandAS = false;
    
    /** Range number (sequence number) */
    public int $rangeNumber = 0;
    
    /** @var DatagramCapsule[] */
    public array $capsules = [];
    
    /**
     * Mã hóa ValidDatagram thành bytes
     * 
     * @return string
     */
    public function encode(): string
    {
        $buf = '';
        
        // Flags byte
        $flags = 0x00;
        if ($this->isPacketPair) $flags |= 0x01;
        if ($this->isContinuousSend) $flags |= 0x02;
        if ($this->requiresBandAS) $flags |= 0x04;
        // Bit 3-7: reserved
        $buf .= chr($flags);
        
        // Range number (uint24, little endian)
        $buf .= pack('V', $this->rangeNumber) & "\xFF\xFF\xFF";
        
        // Capsules
        foreach ($this->capsules as $capsule) {
            $buf .= $capsule->encode();
        }
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành ValidDatagram
     * 
     * @param string $data
     * @param int $offset
     * @return array [ValidDatagram, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $datagram = new self();
        
        // Flags
        $flags = ord($data[$offset++]);
        $datagram->isPacketPair = ($flags & 0x01) !== 0;
        $datagram->isContinuousSend = ($flags & 0x02) !== 0;
        $datagram->requiresBandAS = ($flags & 0x04) !== 0;
        
        // Range number (uint24, little endian)
        $rangeBytes = substr($data, $offset, 3) . "\x00";
        $datagram->rangeNumber = unpack('V', $rangeBytes)[1] & 0xFFFFFF;
        $offset += 3;
        
        // Capsules (đọc cho đến hết data)
        while ($offset < strlen($data)) {
            [$capsule, $offset] = DatagramCapsule::decode($data, $offset);
            $datagram->capsules[] = $capsule;
        }
        
        return [$datagram, $offset];
    }
    
    /**
     * Thêm capsule vào datagram
     * 
     * @param DatagramCapsule $capsule
     */
    public function addCapsule(DatagramCapsule $capsule): void
    {
        $this->capsules[] = $capsule;
    }
}

