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
        return DB::funquery("SELECT * FROM notification WHERE id_user = ? AND expires >= ? ORDER BY stamp DESC", [$id_user, date('Y-m-d H:i:s')])->map(function ($x) {
            $x->expiresRelative = $x->expires == null ? null : (strtotime($x->expires) - microtime(true));
            return $x;
        })->toArray();
    }

    public function hide(int $id, int $userId)
    {
        DB::query("DELETE FROM notification WHERE id = ? AND id_user = ?", [$id, $userId]);
    }
}