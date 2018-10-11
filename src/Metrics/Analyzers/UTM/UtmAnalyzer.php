<?php

namespace Code16\Metrics\Analyzers\UTM;

use Code16\Metrics\Analyzers\Analyzer;
use Illuminate\Support\Collection;
use Code16\Metrics\TimeInterval;

abstract class UtmAnalyzer extends Analyzer
{
    protected $field;

    /**
     * Extract statistics from a range of visits
     *
     * @param  Collection $visits [description]
     * @param TimeInterval $interval
     * @return array
     */
    public function compile(Collection $visits, TimeInterval $interval)
    {
        $field = $this->field;
        $values = [];

        foreach($visits as $visit) {
            $value = $visit->hasCustomValue($field)
                ? $visit->getCustomValue($field)
                : null;

            if($value) {
                $values[$value] = ($values[$value] ?? 0) + 1;
            }
        }

        return $values;
    }


    /**
     * Consolidate several metrics objects
     *
     * @param Collection $metrics
     * @param TimeInterval $interval
     * @return array
     */
    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $combined = [];

        foreach ($metrics as $metric) {
            $items = $metric->getStatisticsByKey(get_class($this));

            foreach($items as $id => $hits) {
                $combined[$id] = ($combined[$id] ?? 0) + $hits;
            }
        }

        return $combined;
    }
}