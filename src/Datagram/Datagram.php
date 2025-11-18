<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Datagram;

use function chr;
use function ord;
use function substr;

/**
 * Base Datagram class - Xử lý phân loại và mã hóa/giải mã datagrams
 * 
 * Datagram có thể là:
 * - Valid Datagram (chứa packets)
 * - ACK Datagram (xác nhận)
 * - NACK Datagram (báo thiếu)
 */
class Datagram
{
    /** Có phải valid datagram không */
    public bool $isValid = false;
    
    /** Có phải ACK datagram không */
    public bool $isAck = false;
    
    /** Có phải NACK datagram không */
    public bool $isNack = false;
    
    /** ValidDatagram instance (nếu isValid = true) */
    public ?ValidDatagram $validDatagram = null;
    
    /** AckDatagram instance (nếu isAck = true) */
    public ?AckDatagram $ackDatagram = null;
    
    /** NackDatagram instance (nếu isNack = true) */
    public ?NackDatagram $nackDatagram = null;
    
    /**
     * Mã hóa Datagram thành bytes
     * 
     * @return string
     */
    public function encode(): string
    {
        $buf = '';
        
        // Flags byte đầu tiên
        $flags = 0x00;
        if ($this->isValid) $flags |= 0x01;
        if ($this->isAck) $flags |= 0x02;
        if ($this->isNack) $flags |= 0x04;
        // Bit 3-7: reserved
        $buf .= chr($flags);
        
        // Encode datagram tương ứng
        if ($this->isValid && $this->validDatagram !== null) {
            $buf .= $this->validDatagram->encode();
        } elseif ($this->isAck && $this->ackDatagram !== null) {
            $buf .= $this->ackDatagram->encode();
        } elseif ($this->isNack && $this->nackDatagram !== null) {
            $buf .= $this->nackDatagram->encode();
        }
        
        return $buf;
    }
    
    /**
     * Giải mã bytes thành Datagram
     * 
     * @param string $data
     * @return Datagram|null
     */
    public static function decode(string $data): ?self
    {
        if (empty($data)) {
            return null;
        }
        
        $datagram = new self();
        $offset = 0;
        
        // Flags byte đầu tiên
        $flags = ord($data[$offset++]);
        $datagram->isValid = ($flags & 0x01) !== 0;
        $datagram->isAck = ($flags & 0x02) !== 0;
        $datagram->isNack = ($flags & 0x04) !== 0;
        
        // Decode datagram tương ứng
        $remaining = substr($data, $offset);
        
        if ($datagram->isValid) {
            [$validDatagram, ] = ValidDatagram::decode($remaining, 0);
            $datagram->validDatagram = $validDatagram;
        } elseif ($datagram->isAck) {
            [$ackDatagram, ] = AckDatagram::decode($remaining, 0);
            $datagram->ackDatagram = $ackDatagram;
        } elseif ($datagram->isNack) {
            [$nackDatagram, ] = NackDatagram::decode($remaining, 0);
            $datagram->nackDatagram = $nackDatagram;
        } else {
            // Không phải loại datagram nào đã biết
            return null;
        }
        
        return $datagram;
    }
    
    /**
     * Tạo ValidDatagram
     * 
     * @param ValidDatagram $validDatagram
     * @return self
     */
    public static function createValid(ValidDatagram $validDatagram): self
    {
        $datagram = new self();
        $datagram->isValid = true;
        $datagram->validDatagram = $validDatagram;
        return $datagram;
    }
    
    /**
     * Tạo AckDatagram
     * 
     * @param AckDatagram $ackDatagram
     * @return self
     */
    public static function createAck(AckDatagram $ackDatagram): self
    {
        $datagram = new self();
        $datagram->isAck = true;
        $datagram->ackDatagram = $ackDatagram;
        return $datagram;
    }
    
    /**
     * Tạo NackDatagram
     * 
     * @param NackDatagram $nackDatagram
     * @return self
     */
    public static function createNack(NackDatagram $nackDatagram): self
    {
        $datagram = new self();
        $datagram->isNack = true;
        $datagram->nackDatagram = $nackDatagram;
        return $datagram;
    }
}

