<?php

namespace Notifications\Repository;


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
}