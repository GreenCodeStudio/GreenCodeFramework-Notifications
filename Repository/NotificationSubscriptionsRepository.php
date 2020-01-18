<?php

namespace Notifications\Repository;


use Core\DB;

class NotificationSubscriptionsRepository extends \Core\Repository
{

    public function __construct()
    {
        $this->archiveMode = static::ArchiveMode_OnlyExisting;
    }

    public function defaultTable(): string
    {
        return "notification_subscription";
    }

    public function getForUser($id_user)
    {
        return DB::get("SELECT * FROM notification_subscription WHERE id_user = ?", [$id_user]);
    }
}