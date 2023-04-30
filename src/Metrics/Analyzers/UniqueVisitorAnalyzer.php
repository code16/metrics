<?php

namespace Code16\Metrics\Analyzers;

use Code16\Metrics\Metric;
use Illuminate\Support\Collection;
use Code16\Metrics\TimeInterval;

class UniqueVisitorAnalyzer extends Analyzer
{

    public function compile(Collection $visits, TimeInterval $interval)
    {
        $cookieStack = [];

        foreach($visits as $visit) {
            if(! in_array($visit->getCookie(), $cookieStack)) {
                $cookieStack[] = $visit->getCookie();
            }
        }

        return ['unique-visitors' => count($cookieStack)];
    }

    // This operation will add two array returned by the compile() method
    // then return a consolidated array.
    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $uniqueVisitors = 0;

        foreach($metrics as $metric) {
            $statistic = $metric->getStatisticsByKey(get_class($this));
            if(array_key_exists('unique-visitors', $statistic)) {
                $uniqueVisitors+= $statistic['unique-visitors'];
            }
        }

        $data = ['unique-visitors' => $uniqueVisitors];
        return $data;
    }


}
