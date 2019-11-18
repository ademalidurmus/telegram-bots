<?php namespace AAD\TelegramBots\App\Notes\Storage;

use AAD\TelegramBots\Helper\Language;

use Respect\Validation\Validator as v;
use AAD\TelegramBots\Exceptions\StoragePdoException;
use AAD\TelegramBots\Exceptions\NotFoundException;
use AAD\TelegramBots\Helper\Crypt;

class Pdo
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function create($note)
    {
        $query = "INSERT INTO `notes` (`owner_id`, `content`, `status`, `insert_time`, 'update_time') VALUES (:owner_id, :content, :status, :insert_time, :update_time)";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':owner_id', $note->owner_id, \PDO::PARAM_INT);
        $statement->bindValue(':content', Crypt::encrypt($note->content), \PDO::PARAM_STR);
        $statement->bindValue(':status', $note->status, \PDO::PARAM_STR);
        $statement->bindValue(':insert_time', $note->insert_time, \PDO::PARAM_INT);
        $statement->bindValue(':update_time', $note->update_time, \PDO::PARAM_INT);
        if ($statement->execute()) {
            $note->id = $this->pdo->lastInsertId();
            return $note;
        }
        throw new StoragePdoException(Language::set([
            "en::Note create failed.",
            "tr::Not kayıt edilirken bir hata oluştu."
        ], 3), 3);
    }

    public function read($note)
    {
        $query = "SELECT * FROM `notes` WHERE `id` = :id AND `owner_id` = :owner_id";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':owner_id', $note->owner_id, \PDO::PARAM_INT);
        $statement->bindValue(':id', $note->id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            if (v::arrayType()->validate($row = $statement->fetch(\PDO::FETCH_ASSOC))) {
                foreach ($row as $key => $value) {
                    $note->{$key} = v::in(['content'])->validate($key) ? Crypt::decrypt($value) : $value;
                }
                return $note;
            }
            throw new NotFoundException(Language::set([
                "en::Note not found.",
                "tr::Not bulunamadı."
            ], 4), 4);
        }
        throw new StoragePdoException(Language::set([
            "en::Note read failed.",
            "tr::Not okunurken bir hata oluştu."
        ], 5), 5);
    }

    public function update($note)
    {
        $note_keys = array_keys((array) $note);
        $update_params = v::arrayType()->validate($note->update_params) ? $note->update_params : [];
        $query = "UPDATE `notes` SET ";
        foreach ($update_params as $key) {
            if (v::in($note_keys)->validate($key)) {
                $query .= "`{$key}` = :{$key},";
            }
        }
        $query = rtrim($query, ",");
        $query .= " WHERE `id` = :id AND `owner_id` = :owner_id";
        $statement = $this->pdo->prepare($query);
        foreach ($update_params as $key) {
            if (v::in($note_keys)->validate($key)) {
                $statement->bindValue(":{$key}", v::in(['content'])->validate($key) ? Crypt::encrypt($note->{$key}) : $note->{$key});
            }
        }
        $statement->bindValue(':id', $note->id, \PDO::PARAM_INT);
        $statement->bindValue(':owner_id', $note->owner_id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            return $note;
        }
        throw new StoragePdoException(Language::set([
            "en::Note update failed.",
            "tr::Not güncellenirken bir hata oluştu."
        ], 6), 6);
    }

    public function delete($note)
    {
        $query = "DELETE FROM `notes` WHERE `id` = :id AND `owner_id` = :owner_id";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $note->id, \PDO::PARAM_INT);
        $statement->bindValue(':owner_id', $note->owner_id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            return true;
        }
        throw new StoragePdoException(Language::set([
            "en::Note delete failed.",
            "tr::Not silinirken bir hata oluştu."
        ], 7), 7);
    }

    public function search($params)
    {
        $query = "SELECT COUNT(*) as `count` FROM `notes` WHERE `owner_id` = :owner_id";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':owner_id', $params->owner_id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            $total = $statement->fetch(\PDO::FETCH_ASSOC);

            $query = "SELECT * FROM `notes` WHERE `owner_id` = :owner_id ";

            if (!v::nullType()->validate($params->sort) && !v::nullType()->validate($params->sort_by)) {
                $query .= "ORDER BY `{$params->sort}` {$params->sort_by} ";
            }

            if (!v::nullType()->validate($params->start) && !v::nullType()->validate($params->count)) {
                $query .= "LIMIT {$params->start}, {$params->count}";
            }

            $statement = $this->pdo->prepare($query);
            $statement->bindValue(':owner_id', $params->owner_id, \PDO::PARAM_INT);
            if ($statement->execute()) {
                $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $key => &$value) {
                    $value['content'] = Crypt::decrypt($value['content']);
                }
                return [
                    'total_count' => $total['count'],
                    'data' => $rows
                ];
            }
        }
        throw new StoragePdoException(Language::set([
            "en::Note search failed.",
            "tr::Notlar listelenirken bir hata oluştu."
        ], 8), 8);
    }
}
