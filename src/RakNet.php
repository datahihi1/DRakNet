<?php

declare(strict_types=1);

namespace Datahihi1\RakNet;

use function ord;
use function strlen;

/**
 * Các hằng số và tiện ích chung của giao thức RakNet
 */
final class RakNet
{
    /** Mã byte ma thuật của giao thức RakNet */
    public const MAGIC = "00ffff00fefefefefdfdfdfd12345678";
    /** Phiên bản giao thức mặc định (mặc định trong Minecraft: Bedrock Edition là v0.2.0_alpha) */
    public const DEFAULT_PROTOCOL_VERSION = 2;
    /** Cổng mặc định của RakNet cho máy chủ Minecraft: Bedrock Edition */
    public const DEFAULT_PORT = 19132;
    /** Cổng mặc định của RakNet cho máy chủ Minecraft: Bedrock Edition (IPv6) */
    public const DEFAULT_PORT_IPV6 = 19133;

    // --- PING PACKET IDS ---
    /** ID cho gói ping đã kết nối */
    public const ID_CONNECTED_PING = 0x00;
    /** ID cho gói ping không kết nối */
    public const ID_UNCONNECTED_PING = 0x01;
    /** ID cho gói ping không kết nối mở */
    public const ID_UNCONNECTED_PING_OPEN_CONNECTIONS = 0x02;

    // --- PONG PACKET IDS ---
    /** ID cho gói pong đã kết nối */
    public const ID_CONNECTED_PONG = 0x03;
    /** ID cho gói pong không kết nối */
    public const ID_UNCONNECTED_PONG = 0x1C;

    // --- CONNECTION PACKET IDS ---
    /** ID cho gói yêu cầu kết nối mở 1 */
    public const ID_OPEN_CONNECTION_REQUEST_1 = 0x05;
    /** ID cho gói phản hồi kết nối mở 1 */
    public const ID_OPEN_CONNECTION_REPLY_1 = 0x06;
    /** ID cho gói yêu cầu kết nối mở 2 */
    public const ID_OPEN_CONNECTION_REQUEST_2 = 0x07;
    /** ID cho gói phản hồi kết nối mở 2 */
    public const ID_OPEN_CONNECTION_REPLY_2 = 0x08;
    /** ID cho gói yêu cầu kết nối */
    public const ID_CONNECTION_REQUEST = 0x09;
    /* ID cho gói hệ thống từ xa yêu cầu khóa công khai */
    public const ID_REMOTE_SYSTEM_REQUIRES_PUBLIC_KEY = 0x0A;
    /* ID cho gói hệ thống của người dùng yêu cầu bảo mật */
    public const ID_OUR_SYSTEM_REQUIRES_SECURITY = 0x0B;
    /* ID cho gói yêu cầu kết nối đã được chấp nhận */
    public const ID_CONNECTION_REQUEST_ACCEPTED = 0x10;
    /* ID cho gói yêu cầu kết nối đã bị từ chối */
    public const ID_CONNECTION_ATTEMPT_FAILED = 0x11;
    /* ID cho gói đã kết nối */
    public const ID_ALREADY_CONNECTED = 0x12;
    /* ID cho gói kết nối mới đến */
    public const ID_NEW_INCOMING_CONNECTION = 0x13;
    /* ID cho gói thông báo không có kết nối đến */
    public const ID_DISCONNECTION_NOTIFICATION = 0x15;
    /* ID cho gói thông báo không có kết nối đến do không còn kết nối */
    public const ID_NO_FREE_INCOMING_CONNECTIONS = 0x14;
    /** ID cho gói thông báo mất kết nối */
    public const ID_CONNECTION_LOST = 0x16;
    /** ID cho gói thông báo phiên bản giao thức không tương thích */
    public const ID_INCOMPATIBLE_PROTOCOL_VERSION = 0x19;
    /** ID cho gói thông báo phiên bản giao thức không tương thích (cũ) */
    public const ID_INCOMPATIBLE_PROTOCOL_VERSION_OLD = 0x1A;
    /**
     * Lấy byte ma thuật của RakNet dưới dạng chuỗi nhị phân
     */
    public static function magicBytes(): string
    {
        return hex2bin(self::MAGIC);
    }

    /**
     * Lấy byte ma thuật của RakNet dưới dạng chuỗi nhị phân
     * 
     * In hex debug view
     * @param string $data Raw data
     * @return string Hex representation
     */
    public static function dumpHex(string $data): string
    {
        $out = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $out .= sprintf('%02X ', ord($data[$i]));
        }
        return trim($out);
    }
}
