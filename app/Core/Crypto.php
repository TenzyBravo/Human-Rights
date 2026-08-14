<?php
declare(strict_types=1);

namespace App\Core;

/**
 * AES-256-GCM encryption for sensitive columns (national IDs, etc.).
 * Ciphertext layout: nonce(12) || tag(16) || ciphertext
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private static function key(): string
    {
        $raw = (string)App::config('security.app_key');
        if (str_starts_with($raw, 'base64:')) {
            $raw = (string)base64_decode(substr($raw, 7), true);
        }
        // Normalise whatever was configured to exactly 32 bytes.
        return hash('sha256', $raw, true);
    }

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }
        $nonce  = random_bytes(12);
        $tag    = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        return $nonce . $tag . $cipher;
    }

    public static function decrypt(?string $blob): ?string
    {
        if ($blob === null || strlen($blob) < 29) {
            return null;
        }
        $nonce  = substr($blob, 0, 12);
        $tag    = substr($blob, 12, 16);
        $cipher = substr($blob, 28);
        $plain  = openssl_decrypt($cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $nonce, $tag);
        return $plain === false ? null : $plain;
    }
}
