<?php


namespace Notifications;

use Authorization\Authorization;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Notifications\Repository\NotificationsRepository;
use Notifications\Repository\NotificationSubscriptionsRepository;

class Notifications
{
    private NotificationsRepository $defaultDB;
    private NotificationSubscriptionsRepository $subscriptionDB;

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
        $data = [
            'id_user' => $notification->id_user,
            'message' => $notification->message ?? "",
            'link' => $notification->link ?? null,
            'stamp' => $stamp,
            'expires' => $notification->expires
        ];
        $this->defaultDB->insert($data);
    }

    private function PushToServiceWorker($notification)
    {
        $subscriptions = $this->subscriptionDB->getForUser($notification->id_user);
        dump($subscriptions);
        $auth = [
            'VAPID' => [
                'subject' => $_ENV['VAPID_subject'],
                'publicKey' => $_ENV['VAPID_publicKey'],
                'privateKey' => $_ENV['VAPID_privateKey'],
            ],
        ];
        $webPush = new WebPush($auth);
        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create(json_decode($subscription->data, true)),
                json_encode($notification),
            );
        }
        foreach ($webPush->flush() as $report) {

            dump($report);
        }
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
            'data' => json_encode($data)
        ];
        return $this->subscriptionDB->insert($row);
    }

    public function hide(int $id)
    {
        $this->defaultDB->hide($id, Authorization::getUserId());
    }
}
