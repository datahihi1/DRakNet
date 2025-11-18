# Hướng Dẫn Xây Dựng RakNet Protocol Full Chuẩn Minecraft

## Tổng Quan

Để xây dựng một RakNet Protocol đầy đủ cho Minecraft Bedrock Edition, bạn cần triển khai các thành phần sau:

### 1. **Offline Packets**
- UnconnectedPing/UnconnectedPong
- OpenConnectionRequest1/Reply1
- OpenConnectionRequest2/Reply2
- IncompatibleProtocolVersion

### 2. **Online Packets**
- ConnectionRequest/ConnectionRequestAccepted
- NewIncomingConnection
- ConnectedPing/ConnectedPong
- DisconnectionNotification/ConnectionLost

### 3. **Datagram System**
Đây là phần quan trọng nhất - xử lý các packet online qua datagrams:

#### 3.1. Datagram Types
- **Valid Datagram**: Chứa các packet thực tế (online packets)
- **ACK Datagram**: Xác nhận đã nhận được datagrams
- **NACK Datagram**: Báo cáo các datagrams bị thiếu

#### 3.2. Datagram Structure
```
Byte 0: Flags (bit field)
  - Bit 0: is_valid (1 = valid datagram)
  - Bit 1: is_ack (1 = ACK datagram)
  - Bit 2: is_nack (1 = NACK datagram)
```

### 4. **Reliability System**

Các loại reliability:
- `0` - Unreliable: Không đảm bảo gửi đến
- `1` - UnreliableSequenced: Có thứ tự nhưng không đảm bảo
- `2` - Reliable: Đảm bảo gửi đến
- `3` - ReliableOrdered: Đảm bảo và có thứ tự
- `4` - ReliableSequenced: Đảm bảo và có thứ tự sequenced
- `5` - UnreliableWithAckReceipt
- `6` - ReliableWithAckReceipt
- `7` - ReliableOrderedWithAckReceipt

### 5. **ACK/NACK System**

#### RangeList Structure
- Chứa danh sách các range numbers đã nhận (ACK) hoặc bị thiếu (NACK)
- Sử dụng Range structure để tối ưu hóa (merge các range liên tiếp)

#### Range Structure
```
- is_single (bool): true nếu min == max
- min (uint24, little endian): Giá trị nhỏ nhất
- max (uint24, little endian): Giá trị lớn nhất (không có nếu is_single = true)
```

### 6. **Split Packet System**

Khi packet quá lớn (vượt quá MTU size), cần chia nhỏ:
- Split packet info: size, id, index
- Reassembly: Ghép lại các phần đã nhận

### 7. **Connection Management**

- MTU Discovery: Tìm MTU size tối ưu
- Retransmission: Gửi lại các packet chưa được ACK
- Reassembly: Ghép lại split packets
- Sequence Number Management: Quản lý sequence numbers cho reliable packets

## Kiến Trúc Đề Xuất

```
src/
├── Protocol/
│   ├── Packet.php (base class - đã có)
│   ├── Offline/ (offline packets - đã có)
│   └── Online/ (online packets - đã có)
├── Datagram/
│   ├── Datagram.php (base datagram class)
│   ├── ValidDatagram.php (valid datagram)
│   ├── AckDatagram.php (ACK datagram)
│   ├── NackDatagram.php (NACK datagram)
│   ├── RangeList.php (ACK/NACK range list)
│   └── Range.php (single range)
├── Reliability/
│   ├── ReliabilityType.php (enum/constants)
│   └── ReliabilityManager.php (quản lý reliability)
├── Capsule/
│   ├── DatagramCapsule.php (capsule trong datagram)
│   └── SplitPacketManager.php (quản lý split packets)
└── Connection/
    ├── ConnectionManager.php (quản lý kết nối)
    ├── MTUDiscovery.php (MTU discovery)
    └── RetransmissionManager.php (retransmission)
```

## Luồng Hoạt Động

### 1. Kết Nối (Connection Flow)
```
Client → UnconnectedPing
Server → UnconnectedPong

Client → OpenConnectionRequest1
Server → OpenConnectionReply1 (với MTU size)

Client → OpenConnectionRequest2
Server → OpenConnectionReply2 (tạo connection)

Client → ConnectionRequest (qua datagram)
Server → ConnectionRequestAccepted (qua datagram)

Client → NewIncomingConnection (qua datagram)
```

### 2. Gửi Packet Online
```
1. Tạo packet (ví dụ: ConnectedPing)
2. Đóng gói vào DatagramCapsule với reliability type
3. Nếu packet quá lớn → Split thành nhiều phần
4. Đóng gói vào ValidDatagram với sequence number
5. Gửi qua socket
6. Chờ ACK, nếu không có → Retransmit
```

### 3. Nhận Packet Online
```
1. Nhận datagram từ socket
2. Phân loại: Valid/ACK/NACK
3. Nếu là ACK → Xóa khỏi retransmission queue
4. Nếu là NACK → Thêm vào retransmission queue
5. Nếu là Valid:
   - Kiểm tra sequence number
   - Xử lý split packets (nếu có)
   - Extract capsules
   - Xử lý reliability
   - Gửi ACK cho các packet đã nhận
   - Gửi NACK cho các packet bị thiếu
```

## Các Hằng Số Quan Trọng

```php
// Từ RakNet.php
const MAXIMUM_MTU_SIZE = 1492; // Minecraft: 1400
const UDP_HEADER_SIZE = 28;
const NUMBER_OF_ORDERED_STREAMS = 32;
const USER_PACKET_ENUM_ID = 0x86;
```

## Lưu Ý Quan Trọng

1. **Endianness**: 
   - uint24, uint32: Little Endian (trong datagram)
   - uint64: Big Endian (trong offline packets)
   - uint16: Big Endian (trong một số trường)

2. **MTU Size**: 
   - Phải trừ UDP header size (28 bytes) khi tính toán
   - Minecraft Bedrock: Maximum MTU = 1400

3. **Sequence Numbers**: 
   - Sử dụng uint24 (0 - 16777215)
   - Tự động wrap around khi đạt max

4. **Reliability**:
   - Split packets KHÔNG được unreliable
   - Phải convert sang reliable nếu cần split

5. **ACK/NACK**:
   - Gửi ACK định kỳ (không cần ACK mỗi packet)
   - Gửi NACK ngay khi phát hiện packet bị thiếu

## Bước Tiếp Theo

Xem các file implementation mẫu trong thư mục `src/Datagram/`, `src/Reliability/`, `src/Capsule/`, và `src/Connection/` để hiểu cách triển khai chi tiết.

## Tài Liệu Chi Tiết

Xem `IMPLEMENTATION_GUIDE.md` để biết cách sử dụng và tích hợp các thành phần vào project của bạn.

