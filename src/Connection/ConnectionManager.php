<?php

namespace Datahihi1\RakNet\Connection;

use Datahihi1\RakNet\Datagram\Datagram;
use Datahihi1\RakNet\Datagram\ValidDatagram;
use Datahihi1\RakNet\Datagram\AckDatagram;
use Datahihi1\RakNet\Datagram\NackDatagram;
use Datahihi1\RakNet\Datagram\RangeList;
use Datahihi1\RakNet\Capsule\DatagramCapsule;
use Datahihi1\RakNet\Capsule\SplitPacketManager;
use Datahihi1\RakNet\Reliability\ReliabilityType;

use function strlen;


/**
 * ConnectionManager - Quản lý kết nối RakNet với retransmission, ACK/NACK, split packets
 * 
 * Đây là lớp cốt lõi để xử lý các datagrams online
 */
class ConnectionManager
{
    /** MTU size */
    private int $mtuSize;
    
    /** Expected sequence number (sequence number tiếp theo mong đợi) */
    private int $expectedSequenceNumber = 0;
    
    /** Sequence number tiếp theo để gửi */
    private int $nextSequenceNumber = 0;
    
    /** Reliable index tiếp theo */
    private int $nextReliableIndex = 0;
    
    /** Sequenced index tiếp theo */
    private int $nextSequencedIndex = 0;
    
    /** Ordering index tiếp theo (per channel) */
    private array $nextOrderingIndex = [];
    
    /** ACK ranges (các sequence numbers đã nhận) */
    private RangeList $ackRanges;
    
    /** NACK ranges (các sequence numbers bị thiếu) */
    private RangeList $nackRanges;
    
    /** Split packet manager */
    private SplitPacketManager $splitManager;
    
    /** @var array<int, PendingPacket> Packets đang chờ ACK */
    private array $pendingPackets = [];
    
    /** Thời gian timeout cho retransmission (milliseconds) */
    private int $retransmissionTimeout = 100;
    
    /** Thời gian gửi ACK định kỳ (milliseconds) */
    private int $ackInterval = 10;
    
    /** Thời gian lần cuối gửi ACK */
    private int $lastAckTime = 0;
    
    public function __construct(int $mtuSize = 1400)
    {
        $this->mtuSize = $mtuSize;
        $this->ackRanges = new RangeList();
        $this->nackRanges = new RangeList();
        $this->splitManager = new SplitPacketManager();
    }
    
    /**
     * Gửi một packet với reliability type
     * 
     * @param string $packetData Packet data (đã encode)
     * @param int $reliability Reliability type
     * @param int|null $orderingChannel Ordering channel (nếu ordered)
     * @return string Encoded datagram
     */
    public function sendPacket(string $packetData, int $reliability = ReliabilityType::RELIABLE, ?int $orderingChannel = null): string
    {
        // Kiểm tra và convert unreliable sang reliable nếu cần split
        $packetSize = strlen($packetData);
        $maxPayloadSize = $this->mtuSize - 28; // UDP header size
        $maxBlockSize = $maxPayloadSize - 20; // Trừ đi capsule header size ước tính
        
        if ($packetSize > $maxBlockSize && !ReliabilityType::isReliable($reliability)) {
            // Phải convert sang reliable nếu cần split
            $reliability = ReliabilityType::RELIABLE;
        }
        
        // Tạo capsule
        $capsule = new DatagramCapsule();
        $capsule->reliability = $reliability;
        $capsule->bufferSize = $packetSize * 8; // Convert bytes to bits
        $capsule->buffer = $packetData;
        
        // Set indexes dựa trên reliability type
        if (ReliabilityType::isReliable($reliability)) {
            $capsule->reliableIndex = $this->nextReliableIndex++;
            $this->nextReliableIndex &= 0xFFFFFF; // uint24 wrap
        }
        
        if (ReliabilityType::isSequenced($reliability)) {
            $capsule->sequencedIndex = $this->nextSequencedIndex++;
            $this->nextSequencedIndex &= 0xFFFFFF; // uint24 wrap
        }
        
        if (ReliabilityType::isOrdered($reliability)) {
            if ($orderingChannel === null) {
                $orderingChannel = 0;
            }
            if (!isset($this->nextOrderingIndex[$orderingChannel])) {
                $this->nextOrderingIndex[$orderingChannel] = 0;
            }
            $capsule->orderingIndex = $this->nextOrderingIndex[$orderingChannel]++;
            $capsule->orderingChannel = $orderingChannel;
            $this->nextOrderingIndex[$orderingChannel] &= 0xFFFFFF; // uint24 wrap
        }
        
        // Xử lý split nếu cần
        if ($packetSize > $maxBlockSize) {
            $parts = $this->splitManager->split($packetData, $maxBlockSize);
            // TODO: Implement split packet handling đầy đủ
            // Tạm thời: chỉ xử lý phần đầu tiên
            $capsule->isSplit = true;
            // Split info sẽ được set khi encode
        }
        
        // Tạo ValidDatagram
        $validDatagram = new ValidDatagram();
        $validDatagram->rangeNumber = $this->nextSequenceNumber++;
        $validDatagram->rangeNumber &= 0xFFFFFF; // uint24 wrap
        $validDatagram->addCapsule($capsule);
        
        // Lưu vào pending nếu reliable
        if (ReliabilityType::isReliable($reliability)) {
            $pending = new PendingPacket();
            $pending->sequenceNumber = $validDatagram->rangeNumber;
            $pending->data = Datagram::createValid($validDatagram)->encode();
            $pending->sendTime = (int)(microtime(true) * 1000);
            $this->pendingPackets[$validDatagram->rangeNumber] = $pending;
        }
        
        // Tạo và encode Datagram
        $datagram = Datagram::createValid($validDatagram);
        return $datagram->encode();
    }
    
    /**
     * Nhận và xử lý datagram
     * 
     * @param string $data Raw datagram data
     * @return array|null ['type' => 'valid'|'ack'|'nack', 'data' => ...] hoặc null nếu lỗi
     */
    public function receiveDatagram(string $data): ?array
    {
        $datagram = Datagram::decode($data);
        if ($datagram === null) {
            return null;
        }
        
        if ($datagram->isValid && $datagram->validDatagram !== null) {
            return $this->handleValidDatagram($datagram->validDatagram);
        } elseif ($datagram->isAck && $datagram->ackDatagram !== null) {
            return $this->handleAckDatagram($datagram->ackDatagram);
        } elseif ($datagram->isNack && $datagram->nackDatagram !== null) {
            return $this->handleNackDatagram($datagram->nackDatagram);
        }
        
        return null;
    }
    
    /**
     * Xử lý ValidDatagram
     */
    private function handleValidDatagram(ValidDatagram $datagram): array
    {
        $sequenceNumber = $datagram->rangeNumber;
        
        // Kiểm tra skipped ranges
        $skippedCount = 0;
        if ($sequenceNumber !== $this->expectedSequenceNumber) {
            $skippedCount = $sequenceNumber - $this->expectedSequenceNumber;
            if ($skippedCount > 1000) {
                if ($skippedCount > 50000) {
                    // NAT related issue
                }
                $skippedCount = 1000;
            }
        }
        
        // Thêm vào ACK ranges
        $this->ackRanges->add($sequenceNumber);
        $this->expectedSequenceNumber = $sequenceNumber + 1;
        $this->expectedSequenceNumber &= 0xFFFFFF; // uint24 wrap
        
        // Thêm các sequence numbers bị thiếu vào NACK
        if ($skippedCount > 0) {
            for ($i = 1; $i <= $skippedCount; $i++) {
                $missingSeq = $sequenceNumber - $i;
                $this->nackRanges->add($missingSeq);
            }
        }
        
        // Extract packets từ capsules
        $packets = [];
        foreach ($datagram->capsules as $capsule) {
            $packets[] = $capsule->buffer;
        }
        
        return [
            'type' => 'valid',
            'sequence_number' => $sequenceNumber,
            'packets' => $packets,
        ];
    }
    
    /**
     * Xử lý AckDatagram
     */
    private function handleAckDatagram(AckDatagram $datagram): array
    {
        // Xóa các packets đã được ACK khỏi pending queue
        foreach ($datagram->ackRanges->getRanges() as $range) {
            for ($seq = $range->min; $seq <= $range->max; $seq++) {
                unset($this->pendingPackets[$seq]);
            }
        }
        
        return [
            'type' => 'ack',
            'ranges' => $datagram->ackRanges,
        ];
    }
    
    /**
     * Xử lý NackDatagram
     */
    private function handleNackDatagram(NackDatagram $datagram): array
    {
        // Thêm các packets bị NACK vào retransmission queue
        // (Có thể implement retransmission logic ở đây)
        
        return [
            'type' => 'nack',
            'ranges' => $datagram->nackRanges,
        ];
    }
    
    /**
     * Tạo ACK datagram để gửi
     * 
     * @return string|null Encoded ACK datagram hoặc null nếu không có gì để ACK
     */
    public function createAckDatagram(): ?string
    {
        if ($this->ackRanges->isEmpty()) {
            return null;
        }
        
        $ackDatagram = new AckDatagram();
        // Copy ranges từ ackRanges
        foreach ($this->ackRanges->getRanges() as $range) {
            $ackDatagram->ackRanges->addRange($range);
        }
        
        // Clear ackRanges sau khi gửi
        $this->ackRanges->clear();
        
        $datagram = Datagram::createAck($ackDatagram);
        return $datagram->encode();
    }
    
    /**
     * Tạo NACK datagram để gửi
     * 
     * @return string|null Encoded NACK datagram hoặc null nếu không có gì để NACK
     */
    public function createNackDatagram(): ?string
    {
        if ($this->nackRanges->isEmpty()) {
            return null;
        }
        
        $nackDatagram = new NackDatagram();
        // Copy ranges từ nackRanges
        foreach ($this->nackRanges->getRanges() as $range) {
            $nackDatagram->nackRanges->addRange($range);
        }
        
        // Clear nackRanges sau khi gửi
        $this->nackRanges->clear();
        
        $datagram = Datagram::createNack($nackDatagram);
        return $datagram->encode();
    }
    
    /**
     * Kiểm tra và retransmit các packets chưa được ACK
     * 
     * @return array<string> Danh sách các datagrams cần retransmit
     */
    public function checkRetransmission(): array
    {
        $now = (int)(microtime(true) * 1000);
        $toRetransmit = [];
        
        foreach ($this->pendingPackets as $seq => $pending) {
            if ($now - $pending->sendTime > $this->retransmissionTimeout) {
                $toRetransmit[] = $pending->data;
                $pending->sendTime = $now; // Update send time
            }
        }
        
        return $toRetransmit;
    }
    
    /**
     * Set MTU size
     */
    public function setMtuSize(int $mtuSize): void
    {
        $this->mtuSize = $mtuSize;
    }
    
    /**
     * Get MTU size
     */
    public function getMtuSize(): int
    {
        return $this->mtuSize;
    }
}

/**
 * Pending Packet
 */
class PendingPacket
{
    public int $sequenceNumber = 0;
    public string $data = '';
    public int $sendTime = 0;
}

