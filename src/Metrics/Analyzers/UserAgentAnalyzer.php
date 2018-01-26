<?php

namespace Code16\Metrics\Analyzers;

use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use Code16\Metrics\TimeInterval;

class UserAgentAnalyzer extends Analyzer
{

    public function compile(Collection $visits, TimeInterval $interval)
    {
        $devices = [];
        $browsers = [];
        $browserVersions = [];
        $platforms = [];
        $platformVersions = [];
        $categories = [];

        $agent = new Agent();

        foreach($visits as $visit) {
            
            $agent->setUserAgent($visit->getUserAgent());
            
            $devices = $this->add($devices, $agent->device());

            $browser = $agent->browser();
            $browsers = $this->add($browsers, $browser);
            $browserString = $browser.'-'.$agent->version($browser);
            $browserVersions = $this->add($browserVersions, $browserString);
            
            $platform = $agent->platform();
            $platforms = $this->add($platforms, $platform);
            $platformString = $platform.'-'.$agent->version($platform);
            $platformVersions = $this->add($platformVersions, $platformString);
            
            $categoryString = 'Unknown';
            if($agent->isDesktop()) {
                $categoryString = 'Desktop';
            }
            if($agent->isPhone()) {
                $categoryString = 'Mobile';
            }
            if($agent->isRobot()) {
                $categoryString = 'Robot';
            }

            $categories = $this->add($categories, $categoryString);
        }

        return [
            'devices' => $devices,
            'browsers' => $browsers,
            'browser-versions' => $browserVersions,
            'platforms' => $platforms,
            'platform-versions' => $platformVersions,
            'categories' => $categories,
        ];
    }
    
    /**
     * Process a count of the given values
     * 
     * @param array  $array
     * @param string $value
     */
    protected function add(array $array, $value)
    {
        if(! is_string($value)) {
            return $array;
        }
        if(array_key_exists($value, $array)) {
            $array[$value] += 1;
        }
        else {
            $array[$value] = 1;
        }
        return $array;
    }

    /**
     * Sum numeric values of all array's keys
     * 
     * @param array $a
     * @param array $b
     * @return array
     */
    public function addArrayValues(array $a, array $b)
    {
        $compiledArray = [];
        foreach(array_keys($a) as $key) {
            $compiledArray[$key] = [];
            foreach(array_keys($b[$key]) as $bKey) {
                if(array_key_exists($bKey, $a[$key])) {
                    $compiledArray[$key][$bKey] = $a[$key][$bKey] + $b[$key][$bKey];
                }
                else {
                    $compiledArray[$key][$bKey] = $b[$key][$bKey];
                }
            }
            foreach(array_keys($a[$key]) as $aKey) {
                if(! array_key_exists($aKey, $b[$key])) {
                    $compiledArray[$key][$aKey] = $a[$key][$aKey];
                }
            };
        }
        return $compiledArray;
    }

    /**
     * Consolidate User Agent Metrics
     * 
     * @param  Collection $metrics 
     * @return array
     */
    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $self = $this;

        // Filter out Metrics that would have no data
        $metrics = $metrics->filter(function($item) use ($self) {
            $stats = $item->getStatisticsByKey(get_class($self));
            return count($stats) == 0 ? false : true;
        });
        
        $data = $metrics->reduce(function ($carry, $item) use ($self) {
            $stats = $item->getStatisticsByKey(get_class($self));
            return $this->addArrayValues($carry, $stats);
        }, [
            'devices' => [],
            'browsers' => [],
            'browser-versions' => [],
            'platforms' => [],
            'platform-versions' => [],
            'categories' => [],
        ]);
        return $data;
    }


}