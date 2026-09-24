<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

class EncryptionService
{
    /**
     * Encrypt a string for safe DB storage.
     */
    public static function encrypt(string $plainText): string
    {
        return Crypt::encryptString($plainText);
    }

    /**
     * Decrypt a stored string.
     */
    public static function decrypt(string $cipherText): string
    {
        try {
            return Crypt::decryptString($cipherText);
        } catch (DecryptException $e) {
            throw new InvalidArgumentException('Unable to decrypt the stored value. The encryption key may have changed.');
        }
    }
}
