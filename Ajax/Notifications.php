<?php

namespace Notifications\Ajax;

class Notifications extends \Core\AjaxController
{
    public function subscribePush($data)
    {
        $Balance = new \Notifications\Notifications();
        $Balance->subscribePush($data);
    }
}