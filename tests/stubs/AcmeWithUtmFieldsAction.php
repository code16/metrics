<?php

namespace Code16\Metrics\Tests\Stubs;

class AcmeWithUtmFieldsAction extends AcmeAction {

    public function __construct($value)
    {
        parent::__construct($value);
        $this->addUtmFields();
    }
}
