<?php namespace AAD\TelegramBots\Helper;

use Respect\Validation\Validator as v;

class Config
{
    protected static $config;

    public static function init($file_path)
    {
        $config = parse_ini_file($file_path, true, INI_SCANNER_TYPED);
        
        return self::$config = self::parse($config);
    }

    public static function parse($config, $first = true)
    {
        $obj = new Config();

        foreach ($config as $key => & $value) {
            if (v::arrayType()->validate($value)) {
                $value = self::parse($value, false);
            }
        }

        if ($first) {
            array_walk(
                $config,
                function ($v, $k) use ($obj) {
                    $obj->{$k} = $v;
                }
            );
            return $obj;
        }
        
        return $config;
    }

    public static function get($section = null)
    {
        if (isset($section) && isset(self::$config->{$section})) {
            return (object) self::$config->{$section};
        }
        return self::$config;
    }

    public function __set($name, $value)
    {
        return $this->{$name} = $value;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }

        return null;
    }
}
