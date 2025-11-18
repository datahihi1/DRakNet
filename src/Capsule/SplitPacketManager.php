<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Capsule;

use function ceil;
use function strlen;

/**
 * SplitPacketManager - Quản lý việc chia và ghép lại split packets
 */
class SplitPacketManager
{
    /** @var array<string, SplitPacketBuffer> */
    private array $buffers = [];
    
    /** Next split packet ID */
    private int $nextSplitId = 0;
    
    /**
     * Tạo split packet ID mới
     * 
     * @return int
     */
    public function generateSplitId(): int
    {
        return $this->nextSplitId++ & 0xFFFF; // uint16
    }
    
    /**
     * Chia packet thành nhiều phần nếu cần
     * 
     * @param string $data Packet data
     * @param int $maxBlockSize Maximum block size (bytes)
     * @return SplitPacketPart[]
     */
    public function split(string $data, int $maxBlockSize): array
    {
        $dataLen = strlen($data);
        
        // Nếu không cần split
        if ($dataLen <= $maxBlockSize) {
            return [];
        }
        
        $splitId = $this->generateSplitId();
        $splitCount = (int)ceil($dataLen / $maxBlockSize);
        $parts = [];
        
        for ($i = 0; $i < $splitCount; $i++) {
            $startOffset = $i * $maxBlockSize;
            $bytesToSend = $dataLen - $startOffset;
            
            if ($bytesToSend > $maxBlockSize) {
                $bytesToSend = $maxBlockSize;
            }
            
            $part = new SplitPacketPart();
            $part->id = $splitId;
            $part->index = $i;
            $part->totalSize = $dataLen;
            $part->data = substr($data, $startOffset, $bytesToSend);
            
            $parts[] = $part;
        }
        
        return $parts;
    }
    
    /**
     * Thêm một phần của split packet vào buffer
     * 
     * @param string $key Unique key (thường là address:port)
     * @param int $splitId Split packet ID
     * @param int $index Index của phần này
     * @param string $data Data của phần này
     * @return string|null Complete packet data nếu đã đủ, null nếu chưa
     */
    public function addPart(string $key, int $splitId, int $index, string $data): ?string
    {
        $bufferKey = "{$key}:{$splitId}";
        
        if (!isset($this->buffers[$bufferKey])) {
            $this->buffers[$bufferKey] = new SplitPacketBuffer();
            $this->buffers[$bufferKey]->splitId = $splitId;
        }
        
        $buffer = $this->buffers[$bufferKey];
        $buffer->parts[$index] = $data;
        
        // Kiểm tra xem đã đủ chưa (cần biết total size từ phần đầu tiên)
        // Giả sử phần đầu tiên (index 0) chứa thông tin về total size
        // Hoặc bạn có thể truyền totalSize từ bên ngoài
        
        // Tạm thời: kiểm tra nếu có đủ parts (cần biết total count)
        // Đây là implementation đơn giản, có thể cải thiện
        
        return null; // Tạm thời return null, cần implement logic đầy đủ hơn
    }
    
    /**
     * Xóa buffer của split packet
     * 
     * @param string $key
     * @param int $splitId
     */
    public function removeBuffer(string $key, int $splitId): void
    {
        $bufferKey = "{$key}:{$splitId}";
        unset($this->buffers[$bufferKey]);
    }
    
    /**
     * Xóa tất cả buffers (cleanup)
     */
    public function clear(): void
    {
        $this->buffers = [];
    }
}

/**
 * Split Packet Part
 */
class SplitPacketPart
{
    public int $id = 0;
    public int $index = 0;
    public int $totalSize = 0;
    public string $data = '';
}

/**
 * Split Packet Buffer
 */
class SplitPacketBuffer
{
    public int $splitId = 0;
    /** @var array<int, string> */
    public array $parts = [];
}

