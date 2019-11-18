<?php namespace AAD\TelegramBots\Helper;

use Respect\Validation\Validator as v;

class Bot extends Crud
{
    public static $token;

    public static function setToken($token)
    {
        self::$token = $token;
    }

    public static function send($chat_id, $message, $args = [])
    {
        if (v::arrayType()->validate($message)) {
            $message = json_encode($message);
        }

        $url = "https://api.telegram.org/bot" . self::$token . "/sendMessage?chat_id={$chat_id}&text=" . rawurlencode($message);
        
        foreach ($args as $key => $value) {
            if (v::arrayType()->validate($value)) {
                $value = json_encode($value);
            }
            $url .= "&{$key}=" . rawurlencode($value);
        }

        file_get_contents($url);
    }

    public static function edit($chat_id, $message_id, $message, $args = [])
    {
        if (v::arrayType()->validate($message)) {
            $message = json_encode($message);
        }

        $url = "https://api.telegram.org/bot" . self::$token . "/editMessageText?chat_id={$chat_id}&message_id={$message_id}&text=" . rawurlencode($message);

        foreach ($args as $key => $value) {
            if (v::arrayType()->validate($value)) {
                $value = json_encode($value);
            }
            $url .= "&{$key}=" . rawurlencode($value);
        }

        file_get_contents($url);
    }
}
