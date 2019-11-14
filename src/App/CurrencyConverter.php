<?php namespace AAD\TelegramBots\App;

use AAD\TelegramBots\Helper\Config;

class CurrencyConverter
{
    private $token;

    private static $routes = [
        ['GET', '/currency/{token}/init', 'init'],
    ];

    public static function getRoutes()
    {
        return self::$routes;
    }

    public function __construct($request, $args)
    {
        $this->token = Config::get('bot_tokens')->currency_converter;
        if ($args['token'] !== $this->token) {
            return header('HTTP/1.0 403 Permission Denied');
        }
    }

    public function init($request, $args)
    {
    }
}
