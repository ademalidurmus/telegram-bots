<?php namespace AAD\TelegramBots\Exceptions;

class DefaultException extends \Exception
{
    protected $message;
    protected $identifier;
    protected $code;
    
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function __construct($message, $identifier = 0, $code = 500, \Exception $previous = null)
    {
        $this->message = $message;
        $this->identifier = $identifier;
        $this->code = $code;
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
