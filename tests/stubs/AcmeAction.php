<?php

namespace Code16\Metrics\Tests\Stubs;

use Code16\Metrics\Action;

class AcmeAction extends Action {

    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
