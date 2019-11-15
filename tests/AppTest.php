<?php namespace AAD\TelegramBots;

use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    public function testInit()
    {
        $app = new App();
        $this->assertInstanceOf("\AAD\TelegramBots\App", $app);
    }
}
