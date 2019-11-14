<?php namespace AAD\TelegramBots\App;

use AAD\TelegramBots\Helper\Config;

class Notes
{
    private $token;

    private static $routes = [
        ['GET', '/notes/{token}/init', 'init'],
    ];

    public static function getRoutes()
    {
        return self::$routes;
    }

    public function __construct($request, $args)
    {
        $this->token = Config::get('bot_tokens')->notes_converter;
        if ($args['token'] !== $this->token) {
            return header('HTTP/1.0 403 Permission Denied');
        }
    }

    public function init($request, $args)
    {
    }
}
