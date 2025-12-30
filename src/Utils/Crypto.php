<?php

declare(strict_types=1);

namespace Datahihi1\RakNet\Utils;

use function strlen;

/**
 * Multi-algorithm crypto helper used by examples and handshake demo.
 * Supports:
 * - AES-128-CTR (16-byte key)
 * - XChaCha20-Poly1305 (32-byte key, requires ext/sodium)
 */
class Crypto
{
    private string $key;
    private string $algo;

    public const ALGO_AES_128_CTR = 'aes-128-ctr';
    public const ALGO_XCHACHA20_POLY1305 = 'xchacha20-poly1305';

    /**
     * @param string $key  key bytes (16 bytes for AES-128-CTR, 32 bytes for XChaCha20-Poly1305)
     * @param string $algo algorithm to use (one of class constants)
     */
    public function __construct(string $key, string $algo = self::ALGO_AES_128_CTR)
    {
        $this->algo = $algo;

        if ($algo === self::ALGO_AES_128_CTR) {
            if (strlen($key) !== 16) {
                throw new \InvalidArgumentException('Session key must be 16 bytes for AES-128-CTR');
            }
        } elseif ($algo === self::ALGO_XCHACHA20_POLY1305) {
            if (!defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES')) {
                throw new \RuntimeException('sodium extension is required for xchacha20-poly1305');
            }
            if (strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
                throw new \InvalidArgumentException('Session key must be ' . SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES . ' bytes for xchacha20-poly1305');
            }
        } else {
            throw new \InvalidArgumentException('Unsupported algorithm: ' . $algo);
        }

        $this->key = $key;
    }

    /**
     * Encrypts a plain string and returns nonce||ciphertext (nonce length depends on algo).
     */
    public function encrypt(string $plain): string
    {
        if ($this->algo === self::ALGO_AES_128_CTR) {
            $iv = random_bytes(16);
            $cipher = openssl_encrypt($plain, 'aes-128-ctr', $this->key, OPENSSL_RAW_DATA, $iv);
            if ($cipher === false) {
                throw new \RuntimeException('OpenSSL encrypt failed');
            }
            return $iv . $cipher;
        }

        $nonceBytes = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = random_bytes($nonceBytes);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, '', $nonce, $this->key);
        return $nonce . $cipher;
    }

    /**
     * Decrypts data produced by encrypt().
     */
    public function decrypt(string $data): string
    {
        if ($this->algo === self::ALGO_AES_128_CTR) {
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

        $nonceBytes = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($data) < $nonceBytes) {
            throw new \InvalidArgumentException('Ciphertext too short for xchacha20-poly1305');
        }
        $nonce = substr($data, 0, $nonceBytes);
        $cipher = substr($data, $nonceBytes);
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, '', $nonce, $this->key);
        if ($plain === false) {
            throw new \RuntimeException('Sodium decrypt failed');
        }
        return $plain;
    }

    /**
     * Returns the algorithm in use.
     */
    public function getAlgorithm(): string
    {
        return $this->algo;
    }
}