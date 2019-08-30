<?php


namespace Notifications;

use Notifications\Repository\NotificationsRepository;
use Notifications\Repository\NotificationSubscriptionsRepository;

class Notifications
{
    public function __construct()
    {
        $this->defaultDB = new NotificationsRepository();
        $this->subscriptionDB = new NotificationSubscriptionsRepository();
    }

    public function Push($notification)
    {
        if (empty($notification->id_user)) throw new \InvalidArgumentException('id_user must be specified');
        $this->AddToDb($notification);
        $this->PushToServiceWorker($notification);
    }

    private function AddToDb($notification)
    {
        $stamp = new \DateTime();
        $expires = clone $stamp;
        $expires->add($notification->expirationTime ?? new \DateInterval ('P1M'));
        $data = [
            'id_user' => $notification->id_user,
            'message' => $notification->message ?? "",
            'link' => $notification->link ?? null,
            'stamp' => $stamp,
            'expires' => $expires
        ];
        $this->defaultDB->insert($data);
    }

    private function PushToServiceWorker($notification)
    {
    }

    public function getForCurrentUser()
    {
        return $this->getForUser(\Authorization\Authorization::getUserData()->id);
    }

    public function getForUser(int $id_user)
    {
        return $this->defaultDB->getForUser($id_user);
    }

    public function subscribePush($data)
    {
        $row = [
            'id_user' => \Authorization\Authorization::getUserData()->id,
            'stamp' => new \DateTime(),
            'data' => $data
        ];
        return $this->subscriptionDB->insert($row);
    }
}