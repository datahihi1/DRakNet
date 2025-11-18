<?php
/**
 * Ví dụ sử dụng Datagram System
 * 
 * Demo cách gửi và nhận datagrams với reliability, ACK/NACK
 */

require __DIR__ . '/../vendor/autoload.php';

use Datahihi1\RakNet\Connection\ConnectionManager;
use Datahihi1\RakNet\Reliability\ReliabilityType;
use Datahihi1\RakNet\Protocol\ConnectedPing;

// Tạo ConnectionManager với MTU size 1400 (Minecraft Bedrock)
$connection = new ConnectionManager(1400);

// Ví dụ 1: Gửi ConnectedPing với reliability RELIABLE
echo "=== Ví dụ 1: Gửi ConnectedPing ===\n";
$pingPacket = ConnectedPing::encode();
$datagram = $connection->sendPacket($pingPacket, ReliabilityType::RELIABLE);
echo "Datagram size: " . strlen($datagram) . " bytes\n";
echo "Hex: " . bin2hex($datagram) . "\n\n";

// Ví dụ 2: Nhận và xử lý datagram
echo "=== Ví dụ 2: Nhận và xử lý datagram ===\n";
$result = $connection->receiveDatagram($datagram);
if ($result !== null) {
    echo "Type: " . $result['type'] . "\n";
    if ($result['type'] === 'valid') {
        echo "Sequence number: " . $result['sequence_number'] . "\n";
        echo "Packets count: " . count($result['packets']) . "\n";
    }
}
echo "\n";

// Ví dụ 3: Tạo ACK datagram
echo "=== Ví dụ 3: Tạo ACK datagram ===\n";
$ackDatagram = $connection->createAckDatagram();
if ($ackDatagram !== null) {
    echo "ACK datagram size: " . strlen($ackDatagram) . " bytes\n";
    echo "Hex: " . bin2hex($ackDatagram) . "\n";
} else {
    echo "Không có gì để ACK\n";
}
echo "\n";

// Ví dụ 4: Gửi packet với các reliability types khác nhau
echo "=== Ví dụ 4: Gửi với các reliability types ===\n";
$testData = "Hello RakNet!";

$reliabilities = [
    ReliabilityType::UNRELIABLE => 'Unreliable',
    ReliabilityType::RELIABLE => 'Reliable',
    ReliabilityType::RELIABLE_ORDERED => 'ReliableOrdered',
    ReliabilityType::RELIABLE_SEQUENCED => 'ReliableSequenced',
];

foreach ($reliabilities as $reliability => $name) {
    $datagram = $connection->sendPacket($testData, $reliability);
    echo "{$name}: " . strlen($datagram) . " bytes\n";
}
echo "\n";

// Ví dụ 5: Kiểm tra retransmission
echo "=== Ví dụ 5: Kiểm tra retransmission ===\n";
$toRetransmit = $connection->checkRetransmission();
echo "Packets cần retransmit: " . count($toRetransmit) . "\n";

echo "\n=== Hoàn thành ===\n";

