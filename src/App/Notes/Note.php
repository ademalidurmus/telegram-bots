<?php namespace AAD\TelegramBots\App\Notes;

use AAD\TelegramBots\Helper\Crud;
use AAD\TelegramBots\App\Notes\Storage\Pdo;
use Respect\Validation\Validator as v;

class Note extends Crud
{
    protected $storage;
    protected $update_params = ["content", "status", "update_time"];

    public function __construct($owner_id = null, $id = null, $storage = null)
    {
        $this->id = $id;
        $this->owner_id = $owner_id;
        $this->setStorage($storage);
        if (v::numeric()->validate($this->owner_id) && v::numeric()->validate($this->id)) {
            $this->read();
        }
    }

    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    public function create($data)
    {
        if (!v::numeric()->validate($this->owner_id)) {
            $this->owner_id = $data['owner_id'];
        }
        $this->content = $data['content'];
        $this->status = 'active';
        $this->insert_time = time();
        $this->update_time = time();

        return $this->storage->create($this);
    }

    public function read()
    {
        $this->checkArgs();

        return $this->storage->read($this);
    }

    public function update($data)
    {
        $this->checkArgs();

        if (!v::arrayType()->validate($data)) {
            return $this;
        }

        foreach ($data as $key => $value) {
            if (v::in($this->update_params)->validate($key)) {
                $this->{$key} = $value;
            }
        }

        $this->update_time = time();

        return $this->storage->update($this);
    }

    public function delete()
    {
        $this->checkArgs();

        return $this->storage->delete($this);
    }

    private function checkArgs()
    {
        if (!v::numeric()->oneOf(v::min(1), v::equals(1))->validate($this->id)) {
            throw new StoragePdoException(Language::set([
                "en::Please set a valid note id.",
                "tr::Lütfen geçerli bir not numarası belirtiniz."
            ], 9), 9);
        }

        if (!v::numeric()->oneOf(v::min(1), v::equals(1))->validate($this->owner_id)) {
            throw new StoragePdoException(Language::set([
                "en::Please set a valid account id.",
                "tr::Lütfen geçerli bir kullanıcı numarası belirtiniz."
            ], 10), 10);
        }
    }
}
