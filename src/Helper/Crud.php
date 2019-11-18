<?php namespace AAD\TelegramBots\Helper;

class Crud
{
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
