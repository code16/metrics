<?php

namespace Code16\Metrics\Consoliders;

use Code16\Metrics\Metric;
use Illuminate\Support\Collection;
use Code16\Metrics\Contracts\ConsoliderInterface;

abstract class Consolider implements ConsoliderInterface {

    /**
     * Consolidate several metrics objects
     * 
     * @param  Collection $metrics 
     * @return 
     */
    abstract public function consolidate(Collection $metrics);

}
