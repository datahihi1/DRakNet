<?php

declare(strict_types=1);

namespace Datahihi1\RakNet;

use Datahihi1\RakNet\Protocol\UnconnectedPing;
use Datahihi1\RakNet\Protocol\UnconnectedPong;
use function ord;
use function strlen;
use function is_resource;

/**
 * RakNet Client cho việc gửi Unconnected Ping và nhận Pong
 */
class RakNetClient
{
    /** Tên máy chủ hoặc địa chỉ IP của máy chủ */
    private string $host;

    /** Số cổng của máy chủ */
    private int $port;

    /** Tài nguyên socket */
    private \Socket $socket;

    /** Thời gian chờ (timeout) trong giây để nhận phản hồi */
    private int $timeoutSec = 3;

    /**
     * RakNetClient constructor
     * 
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host, int $port = 19132)
    {
        $this->host = $host;
        $this->port = $port;

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => $this->timeoutSec, "usec" => 0]);
    }

    /**
     * Gửi Unconnected Ping và chờ nhận Pong phản hồi
     * 
     * @return array|null Pong payload đã giải mã hoặc null nếu hết thời gian chờ/không có phản hồi
     */
    public function ping(): ?array
    {
        $packet = UnconnectedPing::encode();
        socket_sendto($this->socket, $packet, strlen($packet), 0, $this->host, $this->port);

        $buf = '';
        $from = '';
        $port = 0;
        $bytes = @socket_recvfrom($this->socket, $buf, 4096, 0, $from, $port);
        if ($bytes === false || $bytes === 0)
            return null;

        $id = ord($buf[0]);
        if ($id === UnconnectedPong::id()) {
            return UnconnectedPong::decode($buf);
        }

        return null;
    }

    /**
     * Đóng socket
     */
    public function close(): void
    {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
    }

    /**
     * Hủy kết nối nếu chưa đóng
     */
    public function __destruct()
    {
        $this->close();
    }
}
