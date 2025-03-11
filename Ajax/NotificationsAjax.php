<?php

namespace Notifications\Ajax;

use Notifications\Notifications;

class NotificationsAjax extends \Core\AjaxController
{
    public function subscribePush($type, $data)
    {
        $notifications = new \Notifications\Notifications();
        $notifications->subscribePush($type, $data);
    }

    public function hide(int $id)
    {
        (new Notifications())->hide($id);
    }
}
