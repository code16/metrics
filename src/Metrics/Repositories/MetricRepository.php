<?php

namespace Code16\Metrics\Repositories;

use Code16\Metrics\Metric;
use Code16\Metrics\TimeInterval;
use Illuminate\Support\Collection;

interface MetricRepository {


    /**
     * Return all metric rows
     * 
     * @return Collection
     */
    public function all();

    /**
     * Return the single object corresponding to the time interval
     *
     * @param  TimeInterval $interval
     * @return  Metric
     */
    public function find(TimeInterval $interval);

    /** 
     * Check if a metric exists in DB
     * 
     * @param  TimeInterval $interval
     * @return boolean               
     */
    public function has(TimeInterval $interval);
    
    /**
     * Return first metric
     * 
     * @return Metric 
     */
    public function first();

    /**
     * Return all the metric records for the given time interval
     * 
     * @param  TimeInterval $interval
     * @return Collection
     */
    public function getTimeInterval(TimeInterval $interval);

    /**
     * Return all the metric records for the given time interval & type
     * 
     * @param  TimeInterval $interval
     * @param  integer  $type
     * @return Collection
     */
    public function getTimeIntervalByType(TimeInterval $interval, $type);

    /**
     * Return true if a time interval exists
     * 
     * @param  TimeInterval $interval 
     * @return boolean             
     */
    public function hasTimeInterval(TimeInterval $interval);

    /**
     * Store a Metric object
     * @param  Metric $metric 
     * @return  void
     */
    public function store(Metric $metric);
}
