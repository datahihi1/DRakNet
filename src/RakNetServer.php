<?php

declare(strict_types=1);

namespace Datahihi1\RakNet;

use Datahihi1\RakNet\Protocol\UnconnectedPing;
use Datahihi1\RakNet\Protocol\UnconnectedPong;
use function ord;
use function strlen;

/**
 * RakNetServer đơn giản trả về MOTD cho Unconnected Ping
 */
class RakNetServer
{
	/** @var string */
	private string $motd;
	/** @var int */
	private int $port;
	/** @var \Socket */
	private \Socket $socket;
	/** @var bool */
	private bool $running = false;

	/**
	 * @param string $motd MOTD trả về cho client
	 * @param int $port Cổng lắng nghe (mặc định 19132)
	 */
	public function __construct(string $motd = 'MCPE;Demo MOTD;2;1.20.0;0;20;1234567890', int $port = 19132)
	{
		$this->motd = $motd;
		$this->port = $port;
		$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->socket, '0.0.0.0', $this->port);
	}

	/**
	 * Chạy server lắng nghe và trả lời Unconnected Ping
	 */
	public function run(): void
	{
		$this->running = true;
		echo "[RakNetServer] Listening on 0.0.0.0:{$this->port}\n";
		while ($this->running) {
			$buf = '';
			$from = '';
			$port = 0;
			$bytes = @socket_recvfrom($this->socket, $buf, 4096, 0, $from, $port);
			if ($bytes === false || $bytes === 0) {
				usleep(10000);
				continue;
			}
			$pid = ord($buf[0]);
			if ($pid === UnconnectedPing::id()) {
				$pong = $this->makePong($buf);
				socket_sendto($this->socket, $pong, strlen($pong), 0, $from, $port);
				echo "[RakNetServer] Replied MOTD to $from:$port\n";
			}
		}
	}

	/**
	 * Tạo gói Unconnected Pong trả về
	 * @param string $pingData
	 * @return string
	 */
	private function makePong(string $pingData): string
	{
		// Lấy timestamp từ ping
		$ts = @unpack('P', substr($pingData, 1, 8))[1] ?? 0;
		$guid = random_int(1, PHP_INT_MAX); // GUID server demo
		$magic = RakNet::magicBytes();
		$motd = $this->motd;
		return chr(UnconnectedPong::id())
			. pack('P', $ts)
			. pack('P', $guid)
			. $magic
			. $motd;
	}

	/**
	 * Dừng server
	 */
	public function stop(): void
	{
		$this->running = false;
		if (is_resource($this->socket)) {
			socket_close($this->socket);
		}
	}

	public function __destruct()
	{
		$this->stop();
	}
}
