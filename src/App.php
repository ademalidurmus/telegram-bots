<?php namespace AAD\TelegramBots;

use AAD\TelegramBots\Helper\Route;
use AAD\TelegramBots\Helper\Config;
use AAD\TelegramBots\Helper\Crypt;
use Respect\Validation\Validator as v;
use AAD\TelegramBots\Exceptions\NotFoundException;
use AAD\TelegramBots\Exceptions\PermissionDeniedException;
use AAD\TelegramBots\Exceptions\StoragePdoException;
use AAD\TelegramBots\Exceptions\UnexpectedValueException;
use AAD\TelegramBots\Exceptions\AuthenticationException;

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
        try {
            Crypt::setKey(Config::get('crypt')->key);
            Crypt::setIv(Config::get('crypt')->iv);

            $this->register("CurrencyConverter");
            $this->register("Notes");
    
            return $this->route->run();
        } catch (UnexpectedValueException $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 406 Not Acceptable");
        } catch (NotFoundException $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        } catch (PermissionDeniedException $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 403 Permission Denied");
        } catch (AuthenticationException $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
        } catch (StoragePdoException $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        } catch (\Throwable $e) {
            return header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        }
    }
}
