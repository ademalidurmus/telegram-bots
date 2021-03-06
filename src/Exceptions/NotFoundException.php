<?php namespace AAD\TelegramBots\Exceptions;

class NotFoundException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = 404, \Exception $previous = null)
    {
        $this->message = $message;
        $this->identifier = $identifier;
        $this->code = $code;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
