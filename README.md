# DRakNet

DRakNet là một thư viện PHP nhỏ thực hiện một phần giao thức RakNet (dành cho Minecraft: Bedrock Edition). Mục đích chính của thư viện là gửi Unconnected Ping và đọc Unconnected Pong, đồng thời hỗ trợ các bước bắt tay ban đầu (OpenConnection Request/Reply).

## Tính năng
- Gửi Unconnected Ping và parse Unconnected Pong (MOTD, số người chơi, version,...)
- Cấu trúc các packet cơ bản: OpenConnectionRequest/Reply, UnconnectedPing/Pong
- Tiện ích đọc/ghi nhị phân và GUID

## Yêu cầu
- PHP 8+
- mở extension sockets (ext-sockets)

- Tùy chọn :
    - ext-openssl (nếu sử dụng TLS sau này)

## Cài đặt
Sử dụng Composer :
```bash
composer require datahihi1/raknet:dev-main
```

Hoặc clone repository và chạy `composer install`

## Ví dụ sử dụng (ping server)
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Datahihi1\RakNet\RakNetClient;

$client = new RakNetClient('example.server.address', 19132);
$response = $client->ping();
if ($response) {
    echo "MOTD: " . ($response['motd_text'] ?? $response['motd']) . PHP_EOL;
    echo "Players: " . ($response['players'] ?? '?') . '/' . ($response['maxPlayers'] ?? '?') . PHP_EOL;
} else {
    echo "No response." . PHP_EOL;
}
$client->close();
```

Kết quả mẫu (với server Cubecraft):
```
MOTD: §f‖ §d§lLUCKY PILLARS + BEDWARS ‖
Players: 6381/55000
```

## API ngắn gọn
- Datahihi1\RakNet\RakNetClient
    - __construct(string $host, int $port = 19132)
    - ping(): ?array — gửi Unconnected Ping, trả về decode của UnconnectedPong hoặc null
    - close(): void

- Datahihi1\RakNet\Protocol\UnconnectedPing
    - static encode(mixed $payload = null): string
    - static decode(string $data): mixed

- Datahihi1\RakNet\Protocol\UnconnectedPong

    - static decode(string $data): mixed — trả về mảng với keys như `timestamp`, `server_guid`, `motd`, `motd_text`, `players`, `maxPlayers`, ...
- Các packet khác (OpenConnectionRequest1/2, OpenConnectionReply1/2) có sẵn để tham khảo và mở rộng.

## Ghi chú
Thư viện hướng tới mục đích học tập / demo — không phải giải pháp production hoàn chỉnh.
Việc giải mã MOTD là "nỗ lực tốt nhất" theo chuẩn Bedrock status string và có thể cần điều chỉnh cho các server có format khác.