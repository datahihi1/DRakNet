<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Capsule;

use Datahihi1\RakNet\Reliability\ReliabilityType;

use function chr;
use function ord;
use function pack;
use function unpack;
use function substr;

/**
 * DatagramCapsule - Đại diện cho một capsule trong ValidDatagram
 * 
 * Mỗi capsule chứa một packet với reliability type cụ thể
 */
class DatagramCapsule
{
    /** Reliability type (3 bits) */
    public int $reliability = 0;
    
    /** Có phải split packet không */
    public bool $isSplit = false;
    
    /** Buffer size (trong bits) */
    public int $bufferSize = 0;
    
    /** Reliable capsule index (uint24, little endian) - chỉ có nếu reliable */
    public ?int $reliableIndex = null;
    
    /** Sequenced capsule index (uint24, little endian) - chỉ có nếu sequenced */
    public ?int $sequencedIndex = null;
    
    /** Ordering index (uint24, little endian) - chỉ có nếu ordered */
    public ?int $orderingIndex = null;
    
    /** Ordering channel (uint8) - chỉ có nếu ordered */
    public ?int $orderingChannel = null;
    
    /** Split packet info - chỉ có nếu isSplit = true */
    public ?SplitPacketInfo $splitInfo = null;
    
    /** Buffer data (packet payload) */
    public string $buffer = '';
    
    /**
     * Tính toán kích thước của capsule header (không bao gồm buffer)
     * 
     * @return int Size in bytes
     */
    public function getHeaderSize(): int
    {
        $size = 1; // reliability (3 bits) + isSplit (1 bit) + buffer size (2 bytes) = 1 byte đầu
        
        // Buffer size (uint16, big endian) - 2 bytes
        $size += 2;
        
        // Reliable index (uint24, little endian) - 3 bytes
        if (ReliabilityType::isReliable($this->reliability)) {
            $size += 3;
        }
        
        // Sequenced index (uint24, little endian) - 3 bytes
        if (ReliabilityType::isSequenced($this->reliability)) {
            $size += 3;
        }
        
        // Ordering index + channel (uint24 + uint8) - 4 bytes
        if (ReliabilityType::isOrdered($this->reliability)) {
            $size += 4; // 3 bytes index + 1 byte channel
        }
        
        // Split packet info (size + id + index) - 10 bytes
        if ($this->isSplit && $this->splitInfo !== null) {
            $size += 10; // 4 bytes size + 2 bytes id + 4 bytes index
        }
        
        return $size;
    }
    
    /**
     * Mã hóa capsule thành bytes
     * 
     * @return string
     */
    public function encode(): string
    {
        $buf = '';
        
        // Byte đầu: reliability (3 bits) + isSplit (1 bit) + reserved (4 bits)
        $firstByte = ($this->reliability & 0x07) | ($this->isSplit ? 0x08 : 0x00);
        $buf .= chr($firstByte);
        
        // Buffer size (uint16, big endian)
        $buf .= pack('n', $this->bufferSize);
        
        // Reliable index (uint24, little endian)
        if (ReliabilityType::isReliable($this->reliability)) {
            $buf .= pack('V', $this->reliableIndex ?? 0) & "\xFF\xFF\xFF";
        }
        
        // Sequenced index (uint24, little endian)
        if (ReliabilityType::isSequenced($this->reliability)) {
            $buf .= pack('V', $this->sequencedIndex ?? 0) & "\xFF\xFF\xFF";
        }
        
        // Ordering index + channel
        if (ReliabilityType::isOrdered($this->reliability)) {
            // Ordering index (uint24, little endian)
            $buf .= pack('V', $this->orderingIndex ?? 0) & "\xFF\xFF\xFF";
            // Ordering channel (uint8)
            $buf .= chr($this->orderingChannel ?? 0);
        }
        
        // Split packet info
        if ($this->isSplit && $this->splitInfo !== null) {
            // Size (uint32, big endian)
            $buf .= pack('N', $this->splitInfo->size);
            // ID (uint16, big endian)
            $buf .= pack('n', $this->splitInfo->id);
            // Index (uint32, big endian)
            $buf .= pack('N', $this->splitInfo->index);
        }
        
        // Buffer data
        $buf .= $this->buffer;
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành DatagramCapsule
     * 
     * @param string $data
     * @param int $offset
     * @return array [DatagramCapsule, new_offset]
     */
    public static function decode(string $data, int $offset = 0): array
    {
        $capsule = new self();
        
        // Byte đầu
        $firstByte = ord($data[$offset++]);
        $capsule->reliability = $firstByte & 0x07;
        $capsule->isSplit = ($firstByte & 0x08) !== 0;
        
        // Buffer size (uint16, big endian)
        $capsule->bufferSize = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
        
        // Reliable index
        if (ReliabilityType::isReliable($capsule->reliability)) {
            $indexBytes = substr($data, $offset, 3) . "\x00";
            $capsule->reliableIndex = unpack('V', $indexBytes)[1] & 0xFFFFFF;
            $offset += 3;
        }
        
        // Sequenced index
        if (ReliabilityType::isSequenced($capsule->reliability)) {
            $indexBytes = substr($data, $offset, 3) . "\x00";
            $capsule->sequencedIndex = unpack('V', $indexBytes)[1] & 0xFFFFFF;
            $offset += 3;
        }
        
        // Ordering index + channel
        if (ReliabilityType::isOrdered($capsule->reliability)) {
            $indexBytes = substr($data, $offset, 3) . "\x00";
            $capsule->orderingIndex = unpack('V', $indexBytes)[1] & 0xFFFFFF;
            $offset += 3;
            $capsule->orderingChannel = ord($data[$offset++]);
        }
        
        // Split packet info
        if ($capsule->isSplit) {
            $splitInfo = new SplitPacketInfo();
            // Size (uint32, big endian)
            $splitInfo->size = unpack('N', substr($data, $offset, 4))[1];
            $offset += 4;
            // ID (uint16, big endian)
            $splitInfo->id = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
            // Index (uint32, big endian)
            $splitInfo->index = unpack('N', substr($data, $offset, 4))[1];
            $offset += 4;
            $capsule->splitInfo = $splitInfo;
        }
        
        // Buffer data (bufferSize is in bits, convert to bytes)
        $bufferBytes = (int)ceil($capsule->bufferSize / 8);
        $capsule->buffer = substr($data, $offset, $bufferBytes);
        $offset += $bufferBytes;
        
        return [$capsule, $offset];
    }
}

/**
 * Split Packet Info
 */
class SplitPacketInfo
{
    /** Total size of split packet (uint32) */
    public int $size = 0;
    
    /** Split packet ID (uint16) */
    public int $id = 0;
    
    /** Index of this split (uint32) */
    public int $index = 0;
}

