<?php

namespace Code16\Metrics\Analyzers;

use Illuminate\Support\Collection;
use Code16\Metrics\TimeInterval;

class UrlAnalyzer extends Analyzer
{

    public function compile(Collection $visits, TimeInterval $interval)
    {
        $stack = [];

        foreach($visits as $visit) {
            
            $url = $this->stripQuery($visit->getUrl());

            if(array_key_exists($url, $stack)) {
                $stack[$url]++;
            }
            else {
                $stack[$url] = 1;
            }
        }

        return $stack;
    }

    protected function stripQuery($url)
    {
        return preg_replace('/\?.*/', '', $url);
    }

    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $newStatistics = [];
        
        foreach ($metrics as $metric) {
            $stat = $metric->getStatisticsByKey(get_class($this));
            foreach($stat as $url => $count)
            {
                if(array_key_exists($url, $newStatistics)) {
                    $newStatistics[$url] = $newStatistics[$url] + $count;
                }      
                else {
                    $newStatistics[$url] = $count;
                }
            }
        }

        return $newStatistics;


    }


}