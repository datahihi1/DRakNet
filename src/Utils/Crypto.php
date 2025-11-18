<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Utils;

use function strlen;

/**
 * Hỗ trợ AES-128-CTR phiên đơn giản.
 */
class Crypto
{
    private string $key;

    /**
     * @param string $key 16-byte session key
     */
    public function __construct(string $key)
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Session key must be 16 bytes');
        }
        $this->key = $key;
    }

    /**
     * Mã hóa dữ liệu sử dụng AES-128-CTR.
     */
    public function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-128-ctr', $this->key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('OpenSSL encrypt failed');
        }
        return $iv . $cipher;
    }

    /**
     * Giải mã dữ liệu sử dụng AES-128-CTR.
     */
    public function decrypt(string $data): string
    {
        if (strlen($data) < 16) {
            throw new \InvalidArgumentException('Ciphertext too short');
        }
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        $plain = openssl_decrypt($cipher, 'aes-128-ctr', $this->key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new \RuntimeException('OpenSSL decrypt failed');
        }
        return $plain;
    }
}