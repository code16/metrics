<?php

namespace Code16\Metrics\Contracts;

use Illuminate\Support\Collection;
use Code16\Metrics\TimeInterval;

interface ConsoliderInterface {

    public function consolidate(Collection $metrics, TimeInterval $interval);

}
