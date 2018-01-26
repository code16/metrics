<?php

namespace Code16\Metrics\Tests\Stubs;

use Code16\Metrics\Visit;

class AcmeProvider {

    public function process(Visit $visit)
    {
        $visit->setCustomValue('test', 'test');
    }

}
