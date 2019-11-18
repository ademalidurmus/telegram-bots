<?php namespace AAD\TelegramBots\App\Notes;

use AAD\TelegramBots\Helper\Crud;
use AAD\TelegramBots\App\Notes\Storage\Pdo;
use Respect\Validation\Validator as v;

class Search extends Crud
{
    protected $storage;

    public function __construct($owner_id, $storage = null)
    {
        $this->owner_id = $owner_id;
        $this->setStorage($storage);
    }

    public function setStorage($storage)
    {
        $this->storage = !v::nullType()->validate($storage) ? $storage : new Pdo(new \PDO("sqlite:" . __DIR__ . "/Storage/_data/notes.sqlite3"));
    }
    
    public function init($params)
    {
        foreach (['start', 'count']as $item) {
            if (isset($params[$item])) {
                $this->{$item} = $params[$item];
            }
        }

        if (!isset($params['start']) || !v::numeric()->oneOf(v::min(0), v::equals(0))->validate($params['start'])) {
            $this->start = 0;
        }

        if (!isset($params['count']) || !v::numeric()->oneOf(v::min(1), v::equals(1), v::max(100))->validate($params['count'])) {
            $this->count = 25;
        }

        $this->sort = "insert_time";
        $this->sort_by = "DESC";

        return $this->storage->search($this);
    }
}
