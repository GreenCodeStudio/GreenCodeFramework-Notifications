<?php


namespace Notifications;

use Authorization\Authorization;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Notifications\Repository\NotificationsRepository;
use Notifications\Repository\NotificationSubscriptionsRepository;

use Google\Auth\Credentials\ServiceAccountCredentials;

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
        $this->PushToFirebase($notification);
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
        try {
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
        } catch (\Exception $e) {
            dump($e);
        }
    }

    function pushToFirebase($notification)
    {
        try {
            $keyFilePath = __DIR__ . '/../../firebase.json';

// Załaduj klucz prywatny i uzyskaj token dostępu
            $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $keyFilePath);
            $accessToken = $credentials->fetchAuthToken()['access_token'];

            $url = 'https://fcm.googleapis.com/v1/projects/ems-warehouse-cordova/messages:send';
            $headers = [
                'Authorization: key=' . $_ENV['firebase_push_key'],
                'Content-Type: application/json'
            ];
            $data = [
                'message' => [
                    'topic' => 'main',
                    'notification' => [
                        'title' => 'Test Notification',
                        'body' => 'body'
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        }catch (\Exception $e) {
            dump($e);
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
