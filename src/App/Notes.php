<?php namespace AAD\TelegramBots\App;

use AAD\TelegramBots\App\Notes\Note;
use AAD\TelegramBots\App\Notes\Search;
use AAD\TelegramBots\App\Notes\Storage\Pdo;
use AAD\TelegramBots\Helper\Config;
use AAD\TelegramBots\Helper\Language;
use AAD\TelegramBots\Helper\Bot;
use AAD\TelegramBots\Helper\Crypt;
use Respect\Validation\Validator as v;
use AAD\TelegramBots\Exceptions\AuthenticationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

class Notes
{
    private $token;
    private $storage;

    private static $routes = [
        ['POST', '/notes/{token}/init', 'init'],
    ];

    public static function getRoutes()
    {
        return self::$routes;
    }

    public function __construct($request, $args)
    {
        $this->token = Config::get('bots')->notes['token'];
        if ($args['token'] !== sha1($this->token)) {
            throw new AuthenticationException(Language::set([
                "en::Authentication failed.",
                "tr::Kimlik doğrulama işlemi başarısız oldu."
            ], 1), 1);
        }

        $this->storage = new Pdo(new \PDO("sqlite:" . Config::get('storage')->sqlite));

        Bot::setToken($this->token);
    }

    public function init($request, $args)
    {
        $body = json_decode($request->body, true);
        $input = self::prepareInput($body);

        try {
            switch (true) {
                case $input['chat_text'] === "/start":
                    $message = [
                        "en" => "Hi {$body['message']['from']['first_name']},\nLet's take note!",
                        "tr" => "Merhaba {$body['message']['from']['first_name']},\nHaydi not tut!"
                    ];
                    break;
                case stristr($input['chat_text'], "/search"):
                    return $this->search($request, $args);
                    break;
            
                case stristr($input['chat_text'], "/read") || stristr($input['chat_text'], "/oku"):
                    return $this->read($request, $args);
                    break;

                case stristr($input['chat_text'], "/delete"):
                    return $this->delete($request, $args);
                    break;
                
                default:
                    return $this->create($request, $args);
                    break;
            }
        } catch (\Throwable $th) {
            $identifier = 0;
            if (method_exists($th, 'getIdentifier')) {
                $identifier = $th->getIdentifier();
            }
            $default = [
                'en' => 'Unknown error occured.',
                'tr' => 'Bilinmeyen bir hata oluştu.'
            ];
            $message[$input['language']] = Language::get("{$input['language']}::{$identifier}::{$default[$input['language']]}");
        }

        return Bot::send($input['chat_id'], $message[$input['language']]);
    }

    public static function prepareInput($body)
    {
        if (isset($body['callback_query'])) {
            $callback_data = InlineKeyboardPagination::getParametersFromCallbackData($body['callback_query']['data']);
            $chat_id = $body['callback_query']['from']['id'];
            $chat_text = $callback_data['command'];
            $language = $body['callback_query']['from']['language_code'];
            $message_id = $body['callback_query']['message']['message_id'];
            $type = 'callback';
        } else {
            $chat_id = $body['message']['chat']['id'];
            $chat_text = $body['message']['text'];
            $language = $body['message']['from']['language_code'];
            $message_id = $body['message']['message_id'];
            $type = 'new';
        }

        if (!v::in(['tr', 'en'])->validate($language)) {
            $language = 'en';
        }

        return [
            'chat_id' => $chat_id,
            'chat_text' => $chat_text,
            'message_id' => $message_id,
            'language' => $language,
            'type' => $type,
        ];
    }

    public function search($request, $args)
    {
        $body = json_decode($request->body, true);
        $input = self::prepareInput($body);

        $chat_args = [];
        $start = 0;
        $count = 5;
        $page = 1;
        if ($input['type'] === 'callback') {
            $callback_data = InlineKeyboardPagination::getParametersFromCallbackData($body['callback_query']['data']);
            $start = ($callback_data['new_page'] - 1) * $count;
            $page = $callback_data['new_page'];
        }

        Crypt::setKey(Config::get('crypt')->key . $input['chat_id']);
        Crypt::setIv(Config::get('crypt')->iv . $input['chat_id']);

        $message = [
            'en' => '',
            'tr' => '',
        ];
        
        $search = new Search($input['chat_id'], $this->storage);
        $search_result = $search->init(['start' => $start, 'count' => $count]);

        if ($search_result['total_count'] > 0) {
            $items          = range(0, $search_result['total_count']);
            $command        = '/search';
            $selected_page  = $page;
            $labels         = [
                'default'   => '%d',
                'first'     => '« %d',
                'previous'  => '‹ %d',
                'current'   => '· %d ·',
                'next'      => '%d ›',
                'last'      => '%d »',
            ];

            $ikp = new InlineKeyboardPagination($items, $command);
            $ikp->setMaxButtons(5, true);
            $ikp->setLabels($labels);
            $ikp->setCallbackDataFormat('command={COMMAND}&old_page={OLD_PAGE}&new_page={NEW_PAGE}');
            
            $pagination = $ikp->getPagination($selected_page);
            if (v::notEmpty()->validate($pagination['keyboard'])) {
                $chat_args['reply_markup'] = [
                    'inline_keyboard' => [
                        $pagination['keyboard'],
                    ]
                ];
            }

            $chat_args['parse_mode'] = 'markdown';

            foreach ($search_result['data'] as $key => $value) {
                $message['en'] .= "`" . date("Y-m-d H:i:s", $value['insert_time']) . "`\n" . substr($value['content'], 0, 25) . "...\n/read{$value['id']}\n\n";
                $message['tr'] .= "`" . date("Y-m-d H:i:s", $value['insert_time']) . "`\n" . substr($value['content'], 0, 25) . "...\n/oku{$value['id']}\n\n";
            }

            $end = ($start + $count);
            if ($end > $search_result['total_count']) {
                $end = $search_result['total_count'];
            }

            $message['en'] .= "```\nShowing {$start} to {$end} of {$search_result['total_count']} entries.\n```";
            $message['tr'] .= "```\n{$search_result['total_count']} kayıttan {$start} ile {$end} arasındaki kayıtlar listelendi.\n```";
        } else {
            $message['en'] = "You have no saved notes.";
            $message['tr'] = "Kayıtlı notunuz bulunmamaktadır.";
        }

        if ($input['type'] === 'callback') {
            return Bot::edit($input['chat_id'], $input['message_id'], $message[$input['language']], $chat_args);
        }
        return Bot::send($input['chat_id'], $message[$input['language']], $chat_args);
    }

    public function create($request, $args)
    {
        $body = json_decode($request->body, true);
        $input = self::prepareInput($body);

        Crypt::setKey(Config::get('crypt')->key . $input['chat_id']);
        Crypt::setIv(Config::get('crypt')->iv . $input['chat_id']);

        $note = new Note($input['chat_id'], null, $this->storage);
        $note->create([
            'content' => trim($input['chat_text'])
        ]);
        $message = [
            'en' => "Note has been created as successfully.",
            'tr' => "Notunuz başarılı bir şekilde kayıt edildi."
        ];

        return Bot::send($input['chat_id'], $message[$input['language']]);
    }

    public function read($request, $args)
    {
        $body = json_decode($request->body, true);
        $input = self::prepareInput($body);

        $id = str_replace(["/read", "/oku"], ["", ""], $input['chat_text']);

        Crypt::setKey(Config::get('crypt')->key . $input['chat_id']);
        Crypt::setIv(Config::get('crypt')->iv . $input['chat_id']);

        $keyboard = [
            'en' => [
                'delete' => 'Delete Note',
                'search' => 'Return Back'
            ],
            'tr' => [
                'delete' => 'Notu Sil',
                'search' => 'Geri Dön'
            ]
        ];
        
        $chat_args['reply_markup'] = [
            'inline_keyboard' => [
                [
                    [
                        "text" => $keyboard[$input['language']]['delete'],
                        "callback_data" => "command=/delete{$id}"
                    ],
                    [
                        "text" => $keyboard[$input['language']]['search'],
                        "callback_data" => "command=/search&new_page=1"
                    ]
                ]
            ]
        ];

        $chat_args['parse_mode'] = 'markdown';

        $note = new Note($input['chat_id'], $id, $this->storage);

        $message = [
            'en' => "{$note->content}\n\n`Saved on " . date("Y-m-d H:i:s", $note->insert_time) . "`\n",
            'tr' => "{$note->content}\n\n`" . date("Y-m-d H:i:s", $note->insert_time) . " tarihinde kayıt edildi`\n",
        ];
        
        return Bot::send($input['chat_id'], $message[$input['language']], $chat_args);
    }

    public function delete($request, $args)
    {
        $body = json_decode($request->body, true);
        $input = self::prepareInput($body);

        $id = ltrim($input['chat_text'], "/delete");

        Crypt::setKey(Config::get('crypt')->key . $input['chat_id']);
        Crypt::setIv(Config::get('crypt')->iv . $input['chat_id']);

        $chat_args['parse_mode'] = 'markdown';

        $note = new Note($input['chat_id'], $id, $this->storage);
        $note->delete();

        $message = [
            'en' => "Note has been deleted as successfully.",
            'tr' => "Notunuz başarılı bir şekilde silindi",
        ];
        
        if ($input['type'] === 'callback') {
            $keyboard = [
                'en' => [
                    'search' => 'Return Back'
                ],
                'tr' => [
                    'search' => 'Geri Dön'
                ]
            ];
            
            $chat_args['reply_markup'] = [
                'inline_keyboard' => [
                    [
                        [
                            "text" => $keyboard[$input['language']]['search'],
                            "callback_data" => "command=/search&new_page=1"
                        ]
                    ]
                ]
            ];
            return Bot::edit($input['chat_id'], $input['message_id'], $message[$input['language']], $chat_args);
        }

        return Bot::send($input['chat_id'], $message[$input['language']], $chat_args);
    }
}
