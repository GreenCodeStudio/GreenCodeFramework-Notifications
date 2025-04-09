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
    private static $firebaseAccessToken;
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
        $this->PushToUser($notification);
        $this->PushToFirebaseTest($notification);
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

    private function PushToUser($notification)
    {

        $subscriptions = $this->subscriptionDB->getForUser($notification->id_user);

        foreach ($subscriptions as $subscription) {
            if ($subscription->type == 'web') {
                $this->PushToServiceWorker($subscription, $notification);
            } else if ($subscription->type == 'android') {
                $this->pushToFirebase($subscription, $notification);
            }
        }
    }

    private function PushToServiceWorker($subscription, $notification)
    {
        try {
            $auth = [
                'VAPID' => [
                    'subject' => $_ENV['VAPID_subject'],
                    'publicKey' => $_ENV['VAPID_publicKey'],
                    'privateKey' => $_ENV['VAPID_privateKey'],
                ],
            ];
            $webPush = new WebPush($auth);
            $webPush->queueNotification(
                Subscription::create(json_decode($subscription->data, true)),
                json_encode($notification),
            );

            foreach ($webPush->flush() as $report) {
                dump($report);
            }
        } catch (\Exception $e) {
            dump($e);
        }
    }

    private function authorizeFirebase()
    {
        if (empty(static::$firebaseAccessToken)) {
            $keyFilePath = __DIR__ . '/../../firebase.json';
            $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $keyFilePath);

            static::$firebaseAccessToken = $credentials->fetchAuthToken()['access_token'];
        }
        return static::$firebaseAccessToken;
    }

    function pushToFirebase($subscription, $notification)
    {
        try {
            $url = 'https://fcm.googleapis.com/v1/projects/1037498931384/messages:send';
            $headers = [
                'Authorization: Bearer ' . $this->authorizeFirebase(),
                'Content-Type: application/json'
            ];
            $data = [
                'message' => [
                    'token' => json_decode($subscription->data)->registrationId,
                    'data' => [
                        'title' => $notification->message,
                        'body' => $notification->body ?? '',
                        'payload' => $notification->link
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
        } catch (\Exception $e) {
            dump($e);
        }
    }

    function pushToFirebaseTest()
    {
        try {
            $url = 'https://fcm.googleapis.com/v1/projects/1037498931384/messages:send';
            $headers = [
                'Authorization: Bearer ' . $this->authorizeFirebase(),
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
            $this->checkFirebase(json_decode($response));
            return $response;
        } catch (\Exception $e) {
            dump($e);
        }
    }

    private function checkFirebase($x)
    {
        $url = 'https://fcm.googleapis.com/v1/' . $x->name;
        $headers = [
            'Authorization: Bearer ' . $this->authorizeFirebase(),
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
    }


    public function getForCurrentUser()
    {
        return $this->getForUser(\Authorization\Authorization::getUserData()->id);
    }

    public function getForUser(int $id_user)
    {
        return $this->defaultDB->getForUser($id_user);
    }

    public function subscribePush($type, $data)
    {
        $row = [
            'id_user' => \Authorization\Authorization::getUserData()->id,
            'stamp' => new \DateTime(),
            'data' => json_encode($data),
            'type' => $type
        ];
        return $this->subscriptionDB->insertIfUnique($row);
    }


    public function hide(int $id)
    {
        $this->defaultDB->hide($id, Authorization::getUserId());
    }
}
