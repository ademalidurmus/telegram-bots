<?php namespace AAD\TelegramBots;

use AAD\TelegramBots\Helper\Config;
use AAD\TelegramBots\Helper\Route;
use Respect\Validation\Validator as v;

class App
{
    protected $route;

    public function __construct()
    {
        $this->route = new Route();
    }

    public function register($class)
    {
        $class = "\\AAD\\TelegramBots\\App\\{$class}";
        if (method_exists($class, 'getRoutes')) {
            $routes = $class::getRoutes();
            foreach ($routes as $route) {
                if (v::in(['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'])->validate($route[0]) && method_exists($class, $route[2])) {
                    $this->route->set([
                        'method' => $route[0],
                        'pattern' => $route[1],
                        'target' => "{$class}::{$route[2]}"
                    ]);
                }
            }
        }
    }

    public function run()
    {
        $this->register("CurrencyConverter");
        $this->register("Notes");

        return $this->route->run();
    }
}
