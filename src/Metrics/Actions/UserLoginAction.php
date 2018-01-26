<?php

namespace Code16\Metrics\Actions;

use Code16\Metrics\Action;

class UserLoginAction extends Action {
    
    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

}
