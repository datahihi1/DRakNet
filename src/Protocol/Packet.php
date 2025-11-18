<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Protocol;

/**
 * Mã gói RakNet cơ sở
 */
abstract class Packet
{
    /**
     * Lấy ID gói
     */
    abstract public static function id(): int;

    /**
     * Mã hóa gói thành byte thô
     * @return string
     */
    abstract public static function encode(mixed $payload = null): string;

    /**
     * Giải mã byte thô thành gói (trợ giúp tĩnh)
     */
    abstract public static function decode(string $data): mixed;
}
