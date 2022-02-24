<?php

namespace Notifications\Ajax;

use Notifications\Notifications;

class NotificationsAjax extends \Core\AjaxController
{
    public function subscribePush($data)
    {
        $Balance = new \Notifications\Notifications();
        $Balance->subscribePush($data);
    }

    public function hide(int $id)
    {
        (new Notifications())->hide($id);
    }
}