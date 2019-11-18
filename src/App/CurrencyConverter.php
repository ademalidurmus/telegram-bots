<?php namespace AAD\TelegramBots\App;

use AAD\TelegramBots\Helper\Config;
use AAD\TelegramBots\Helper\Language;
use Respect\Validation\Validator as v;
use AAD\TelegramBots\Exceptions\AuthenticationException;
use AAD\TelegramBots\Exceptions\UnexpectedValueException;

class CurrencyConverter
{
    private $token;

    private static $routes = [
        ['POST', '/currency/{token}/init', 'init'],
    ];

    public static function getRoutes()
    {
        return self::$routes;
    }

    public function __construct($request, $args)
    {
        $this->token = Config::get('bots')->currency['token'];
        if ($args['token'] !== sha1($this->token)) {
            throw new AuthenticationException(Language::set([
                "en::Authentication failed.",
                "tr::Kimlik doğrulama işlemi başarısız oldu."
            ], 1), 1);
        }
    }

    public function init($request, $args)
    {
        $url = "https://api.telegram.org/bot{$this->token}";
        $details = json_decode($request->body, true);
        $chat_id = $details['message']['chat']['id'];
        
        if (!v::numeric()->validate($chat_id)) {
            throw new UnexpectedValueException(Language::set([
                "en::Unexpected chat id.",
                "tr::Sohbet numarası geçersiz."
            ], 2), 2);
        }

        $message = self::prepareResponse($details);
        file_get_contents("{$url}/sendMessage?chat_id={$chat_id}&text={$message}");
    }

    public static function prepareResponse($details)
    {
        $username = strtolower(Config::get('bots')->currency['username']);
        $message = $details['message']['text'];
        $return = '';
        
        switch (strtolower($message)) {
            case "/dolar":
            case "/dolar@{$username}":
            case "dolar":
            case "usd":
            case "amerikan doları":
            case "abd doları":
                $currency_details = self::getCurrencyDetails('USD');
                $return = self::calculate(1, $currency_details);
                break;
                
            case "/euro":
            case "/euro@{$username}":
            case "euro":
            case "avro":
                $currency_details = self::getCurrencyDetails('EUR');
                $return = self::calculate(1, $currency_details);
                break;
                
            case "/start":
            case "/start@{$username}":
                $return = "Merhaba {$details['message']['from']['first_name']},\nDoviz kuru hesaplama aracına hoş geldin.";
                break;
        }
        
        if (!v::notEmpty()->validate($return)) {
            switch (true) {
                case stristr($message, ' dolar'):
                    $item = explode(' dolar', $message, 2);
                    $currency_details = self::getCurrencyDetails('USD');
                    $return = self::calculate($item[0], $currency_details);
                    break;
                    
                case stristr($message, ' euro'):
                    $item = explode(' euro', $message, 2);
                    $currency_details = self::getCurrencyDetails('EUR');
                    $return = self::calculate((float) $item[0], $currency_details);
                    break;
                
                default:
                    $item = explode(' ', $message, 2);
                    $currency_details = self::getCurrencyDetails(strtoupper($item[1]));
                    $return = self::calculate((float) $item[0], $currency_details);
                    break;
            }
        }
        
        return urlencode($return);
    }

    public static function calculate($count, $currency_details)
    {
        if (!isset($currency_details['BanknoteBuying'])) {
            return 'Komut anlaşılamadı';
        }
        
        $buying = $currency_details['BanknoteBuying'];
        $selling = $currency_details['BanknoteSelling'];
        
        if ($count > 0) {
            $buying *= $count;
            $selling *= $count;
        } else {
            $count = 1;
        }
        
        return "{$count} {$currency_details['Isim']}\nALIŞ: {$buying}\nSATIŞ: {$selling}";
    }

    public static function getCurrencyDetails($currency_code = 'USD')
    {
        $return = [];
        $data = simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
        foreach ($data->Currency ?? [] as $key => $val) {
            if (isset($val->attributes()['CurrencyCode']) && $val->attributes()['CurrencyCode'] == $currency_code) {
                $return = (array) $val;
            }
        }
        return $return;
    }
}
