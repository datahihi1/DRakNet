# Hướng Dẫn Triển Khai RakNet Protocol Full

## Tổng Quan

Đây là hướng dẫn chi tiết để triển khai đầy đủ RakNet Protocol cho Minecraft Bedrock Edition, bao gồm các hệ thống chính như Datagram, Reliability, ACK/NACK, Split Packets và Quản Lý Kết Nối.

## Cấu Trúc Thư Mục

```
src/
├── Protocol/          # Offline & Online Packets (đã có)
├── Datagram/          # Datagram System (mới)
│   ├── Datagram.php
│   ├── ValidDatagram.php
│   ├── AckDatagram.php
│   ├── NackDatagram.php
│   ├── RangeList.php
│   └── Range.php
├── Reliability/       # Reliability System (mới)
│   └── ReliabilityType.php
├── Capsule/           # Capsule System (mới)
│   ├── DatagramCapsule.php
│   └── SplitPacketManager.php
└── Connection/        # Connection Management (mới)
    └── ConnectionManager.php
```

## Các Thành Phần Chính

### 1. Datagram System

Datagram là lớp vận chuyển cho các packet online. Có 3 loại:

- **ValidDatagram**: Chứa các packet thực tế
- **AckDatagram**: Xác nhận đã nhận được packets
- **NackDatagram**: Báo cáo packets bị thiếu

**Ví dụ sử dụng:**

```php
use Datahihi1\RakNet\Datagram\ValidDatagram;
use Datahihi1\RakNet\Datagram\Datagram;
use Datahihi1\RakNet\Capsule\DatagramCapsule;

// Tạo ValidDatagram
$validDatagram = new ValidDatagram();
$validDatagram->rangeNumber = 123; // Sequence number

// Tạo capsule chứa packet
$capsule = new DatagramCapsule();
$capsule->reliability = ReliabilityType::RELIABLE;
$capsule->bufferSize = strlen($packetData) * 8; // bits
$capsule->buffer = $packetData;
$capsule->reliableIndex = 0;

$validDatagram->addCapsule($capsule);

// Encode để gửi
$datagram = Datagram::createValid($validDatagram);
$encoded = $datagram->encode();
```

### 2. Reliability System

Reliability xác định cách packet được xử lý:

```php
use Datahihi1\RakNet\Reliability\ReliabilityType;

// Kiểm tra reliability type
ReliabilityType::isReliable(ReliabilityType::RELIABLE); // true
ReliabilityType::isOrdered(ReliabilityType::RELIABLE_ORDERED); // true
ReliabilityType::isSequenced(ReliabilityType::RELIABLE_SEQUENCED); // true

// Lấy tên
echo ReliabilityType::getName(ReliabilityType::RELIABLE); // "Reliable"
```

### 3. Connection Manager

ConnectionManager là lớp chính để quản lý kết nối:

```php
use Datahihi1\RakNet\Connection\ConnectionManager;
use Datahihi1\RakNet\Reliability\ReliabilityType;
use Datahihi1\RakNet\Protocol\ConnectedPing;

// Khởi tạo với MTU size (Minecraft: 1400)
$connection = new ConnectionManager(1400);

// Gửi packet
$pingPacket = ConnectedPing::encode();
$datagram = $connection->sendPacket($pingPacket, ReliabilityType::RELIABLE);

// Nhận và xử lý datagram
$result = $connection->receiveDatagram($receivedData);
if ($result['type'] === 'valid') {
    foreach ($result['packets'] as $packet) {
        // Xử lý packet
    }
}

// Tạo ACK/NACK
$ackDatagram = $connection->createAckDatagram();
$nackDatagram = $connection->createNackDatagram();

// Kiểm tra retransmission
$toRetransmit = $connection->checkRetransmission();
```

## Luồng Hoạt Động Đầy Đủ

### 1. Kết Nối (Connection Handshake)

```php
// Bước 1: UnconnectedPing
$ping = UnconnectedPing::encode();
socket_sendto($socket, $ping, strlen($ping), 0, $host, $port);
$pong = UnconnectedPong::decode($received);

// Bước 2: OpenConnectionRequest1
$req1 = OpenConnectionRequest1::encode();
socket_sendto($socket, $req1, strlen($req1), 0, $host, $port);
$reply1 = OpenConnectionReply1::decode($received);
$mtuSize = $reply1['mtu_size'];

// Bước 3: OpenConnectionRequest2
$req2 = OpenConnectionRequest2::encode([
    'server_address' => $host,
    'server_port' => $port,
    'mtu_size' => $mtuSize,
    'client_guid' => $clientGuid,
]);
socket_sendto($socket, $req2, strlen($req2), 0, $host, $port);
$reply2 = OpenConnectionReply2::decode($received);

// Bước 4: Tạo ConnectionManager với MTU size
$connection = new ConnectionManager($mtuSize);

// Bước 5: ConnectionRequest (qua datagram)
$connReq = ConnectionRequest::encode(['client_guid' => $clientGuid]);
$datagram = $connection->sendPacket($connReq, ReliabilityType::RELIABLE);
socket_sendto($socket, $datagram, strlen($datagram), 0, $host, $port);
```

### 2. Gửi Packet Online

```php
// Tạo packet (ví dụ: ConnectedPing)
$packet = ConnectedPing::encode();

// Gửi qua ConnectionManager
$datagram = $connection->sendPacket(
    $packet,
    ReliabilityType::RELIABLE // hoặc RELIABLE_ORDERED, RELIABLE_SEQUENCED, etc.
);

// Gửi qua socket
socket_sendto($socket, $datagram, strlen($datagram), 0, $host, $port);

// ConnectionManager tự động:
// - Gán sequence number
// - Gán reliable/sequenced/ordering indexes
// - Xử lý split packets nếu cần
// - Lưu vào pending queue để retransmit nếu cần
```

### 3. Nhận Packet Online

```php
// Nhận datagram từ socket
socket_recvfrom($socket, $data, 4096, 0, $from, $port);

// Xử lý qua ConnectionManager
$result = $connection->receiveDatagram($data);

if ($result !== null) {
    switch ($result['type']) {
        case 'valid':
            // Xử lý packets
            foreach ($result['packets'] as $packet) {
                $packetId = ord($packet[0]);
                // Xử lý packet theo ID
            }
            break;
            
        case 'ack':
            // Packets đã được ACK, ConnectionManager tự động xóa khỏi pending
            break;
            
        case 'nack':
            // Packets bị NACK, có thể cần retransmit
            break;
    }
}

// Gửi ACK định kỳ
$ackDatagram = $connection->createAckDatagram();
if ($ackDatagram !== null) {
    socket_sendto($socket, $ackDatagram, strlen($ackDatagram), 0, $host, $port);
}
```

### 4. Retransmission

```php
// Kiểm tra và retransmit định kỳ
$toRetransmit = $connection->checkRetransmission();
foreach ($toRetransmit as $datagram) {
    socket_sendto($socket, $datagram, strlen($datagram), 0, $host, $port);
}
```

## Xử Lý Split Packets

Khi packet quá lớn, ConnectionManager tự động chia nhỏ:

```php
// Packet lớn sẽ tự động được split
$largePacket = str_repeat('x', 2000); // 2000 bytes
$datagram = $connection->sendPacket($largePacket, ReliabilityType::RELIABLE);

// ConnectionManager tự động:
// 1. Phát hiện packet quá lớn
// 2. Chia thành nhiều phần
// 3. Tạo các capsules với split info
// 4. Gửi từng phần

// Ở phía nhận:
// 1. Nhận các phần
// 2. Lưu vào buffer
// 3. Khi đủ các phần → ghép lại
// 4. Xử lý packet hoàn chỉnh
```

## Best Practices

### 1. Reliability Type Selection

- **Unreliable**: Cho dữ liệu không quan trọng, có thể mất (ví dụ: player position updates)
- **Reliable**: Cho dữ liệu quan trọng, không cần thứ tự (ví dụ: chat messages)
- **ReliableOrdered**: Cho dữ liệu quan trọng và cần thứ tự (ví dụ: inventory updates)
- **ReliableSequenced**: Cho dữ liệu quan trọng, cần thứ tự nhưng chỉ cần mới nhất (ví dụ: health updates)

### 2. ACK/NACK Timing

- Gửi ACK định kỳ (không cần ACK mỗi packet)
- Gửi NACK ngay khi phát hiện packet bị thiếu
- Tối ưu hóa bằng cách merge các ranges

### 3. Retransmission

- Đặt timeout hợp lý (100-200ms)
- Giới hạn số lần retransmit
- Xóa khỏi queue khi đã ACK

### 4. MTU Size

- Minecraft Bedrock: 1400 bytes
- Phải trừ UDP header (28 bytes) khi tính toán
- Phải trừ datagram header khi tính max payload size

## Xử Lý Lỗi

```php
try {
    $result = $connection->receiveDatagram($data);
    if ($result === null) {
        // Invalid datagram
        return;
    }
    
    // Xử lý result
} catch (\Exception $e) {
    // Xử lý lỗi
    error_log("Datagram error: " . $e->getMessage());
}
```

## Testing

Xem file `examples/datagram_example.php` để biết cách test các thành phần.

## Lưu Ý Quan Trọng

1. **Endianness**: 
   - uint24, uint32 trong datagram: Little Endian
   - uint64 trong offline packets: Big Endian
   - uint16: Big Endian

2. **Sequence Numbers**: 
   - Sử dụng uint24 (0 - 16777215)
   - Tự động wrap around

3. **Split Packets**: 
   - KHÔNG được unreliable
   - Phải convert sang reliable nếu cần split

4. **Ordering Channels**: 
   - Tối đa 32 channels (0-31)
   - Mỗi channel có ordering index riêng

## Tích Hợp Với Server/Client Hiện Tại

Để tích hợp vào server/client hiện tại:

1. Sau khi nhận `OpenConnectionReply2`, tạo `ConnectionManager`
2. Thay vì gửi packet trực tiếp, sử dụng `ConnectionManager::sendPacket()`
3. Khi nhận data, kiểm tra xem có phải datagram không (byte đầu có flags)
4. Nếu là datagram, xử lý qua `ConnectionManager::receiveDatagram()`
5. Gửi ACK/NACK định kỳ
6. Kiểm tra retransmission định kỳ

## Tài Liệu Tham Khảo

- `GUIDE_RAKNET_FULL.md`: Tổng quan về RakNet Protocol
- `README_Protocol.txt`: Tài liệu chi tiết về protocol
- `examples/datagram_example.php`: Ví dụ sử dụng

## Hỗ Trợ

Nếu gặp vấn đề, kiểm tra:
1. MTU size có đúng không
2. Endianness có đúng không
3. Sequence numbers có wrap around đúng không
4. ACK/NACK có được gửi định kỳ không
5. Retransmission có hoạt động không

