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
     * @return array
     */
    public function compile(Collection $visits, TimeInterval $interval)
    {
        $field = $this->field;
        $values = [];

        foreach($visits as $visit) {
            
            $value = $visit->hasCustomValue($field) ? $visit->getCustomValue($field) : null;

            if($value) {
                $values[$value] = array_key_exists($value, $values)
                    ? $values[$value]++
                    : 1;
            }
        }

        return [$field => $values];
    }   


    /**
     * Consolidate several metrics objects
     * 
     * @param  Collection $statistics
     * @return 
     */
    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $newStatistics = [];

        foreach ($metrics as $metric) {
            $stat = $metric->getStatisticsByKey(get_class($this));
            $newStatistics = $this->array_add($newStatistics, $stat);
        }

        return $newStatistics;
    }

    protected function array_add(array $a, array $b)
    {
        $c = [];
        foreach(array_keys($a) as $key) {
            $c[$key] = array_key_exists($key, $b) 
                ? $a[$key] + $b[$key]
                : $a[$key];
        }
        foreach(array_keys($b) as $key) {
            $c[$key] = array_key_exists($key, $c) 
                ? $c[$key]
                : array_key_exists($key, $a)
                     ? $a[$key] + $b[$key]
                     : $b[$key]; 
        }

        return $c;
    }
}