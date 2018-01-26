<?php

namespace Code16\Metrics\Analyzers;

use Code16\Metrics\Metric;
use Illuminate\Support\Collection;
use Code16\Metrics\Contracts\AnalyzerInterface;
use Code16\Metrics\Contracts\ConsoliderInterface;
use Code16\Metrics\TimeInterval;

abstract class Analyzer implements AnalyzerInterface, ConsoliderInterface {

    /**
     * Extract statistics from a range of visits
     * 
     * @param  Collection $visits [description]
     * @return array
     */
    abstract public function compile(Collection $visits, TimeInterval $interval);


    /**
     * Consolidate several metrics objects
     * 
     * @param  Collection $statistics
     * @return 
     */
    abstract public function consolidate(Collection $metrics, TimeInterval $interval);

}
