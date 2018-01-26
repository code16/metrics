<?php

namespace Code16\Metrics\Contracts;

use Illuminate\Support\Collection;
use Code16\Metrics\TimeInterval;

interface AnalyzerInterface {

    public function compile(Collection $visits, TimeInterval $interval);

}
