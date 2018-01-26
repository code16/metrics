<?php

namespace Code16\Metrics;

use Illuminate\Support\Collection;

class Compiler
{
    /**
     * @var array
     */
    protected $analyzers;

    public function __construct(array $analyzers)
    {
        $this->analyzers = $analyzers;
    }

    /**
     * Compile statistics from a collection of visit objects
     * 
     * @param  Collection $visits 
     * @return array
     */
    public function compile(Collection $visits, TimeInterval $interval)
    {
        $statistics = [];

        foreach($this->analyzers as $analyzer) {
            $statistics[get_class($analyzer)] = $analyzer->compile($visits, $interval);
        }

        return $statistics;
    }

}
