<?php

namespace Notifications\Repository;


use Core\Database\DB;

class NotificationsRepository extends \Core\Repository
{

    public function __construct()
    {
        $this->archiveMode = static::ArchiveMode_OnlyExisting;
    }

    public function defaultTable(): string
    {
        return "notification";
    }
    public function getForUser(int $id_user)
    {
        return DB::get("SELECT * FROM notification WHERE id_user = ? AND expires >= NOW() ORDER BY stamp DESC", [$id_user]);
    }
}