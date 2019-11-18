<?php namespace AAD\TelegramBots\Helper;

use AAD\TelegramBots\Exceptions\NotFoundException;
use Respect\Validation\Validator as v;

class Route
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'HEAD' => [],
        'OPTIONS' => [],
    ];

    public function set($route)
    {
        $this->routes[$route['method']][$route['pattern']] = $route['target'];
    }

    public function hit()
    {
        if (!v::in(array_keys($this->routes))->validate($_SERVER['REQUEST_METHOD'])) {
            return null;
        }

        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as $key => $value) {
            $args = [];
            if ($this->checkPattern($key, $_SERVER['REQUEST_URI'], $args)) {
                $request = (object) [
                    'body' => file_get_contents('php://input'),
                    'pattern' => $key
                ];

                $exp = explode("::", $value, 2);

                $obj = new $exp[0]($request, $args);
                return $obj->{$exp[1]}($request, $args);
            }
        }

        throw new NotFoundException(Language::set([
            "en::Page not found.",
            "tr::Sayfa bulunamadı."
        ], 11), 11);
    }

    public function checkPattern($pattern, $request_uri, &$args)
    {
        $exp_pattern = explode("/", $pattern);
        $exp_request_uri = explode("/", $request_uri);

        if (count($exp_pattern) !== count($exp_request_uri)) {
            return false;
        }

        foreach ($exp_pattern as $key => $value) {
            if (substr($value, 0, 1) === "{" && substr($value, -1) === "}") {
                if (mb_strlen($exp_request_uri[$key]) < 1) {
                    return false;
                }
                $args[rtrim(ltrim($value, "{"), "}")] = $exp_request_uri[$key];
            } elseif ((string) $exp_request_uri[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    public function run()
    {
        return $this->hit();
    }
}
