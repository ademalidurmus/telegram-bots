<?php namespace AAD\TelegramBots\Helper;

use Respect\Validation\Validator as v;

class Crypt
{
    protected static $iv;
    protected static $key;

    public static function setIv($iv)
    {
        self::$iv = $iv;
    }

    public static function setKey($key)
    {
        self::$key = $key;
    }

    public static function encrypt($string)
    {
        if (v::nullType()->validate(self::$key) || v::nullType()->validate(self::$iv)) {
            self::$key = time();
            self::$iv = time();
        }

        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', self::$key);
        $iv = substr(hash('sha256', self::$iv), 0, 16);
    
        return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    }

    public static function decrypt($string)
    {
        if (v::nullType()->validate(self::$key) || v::nullType()->validate(self::$iv)) {
            self::$key = time();
            self::$iv = time();
        }

        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', self::$key);
        $iv = substr(hash('sha256', self::$iv), 0, 16);

        return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
}
