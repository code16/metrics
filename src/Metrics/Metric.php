<?php

namespace Code16\Metrics;

use Carbon\Carbon;

class Metric
{
    const HOURLY = 1;
    const DAILY = 2;
    const MONTHLY = 3;
    const YEARLY = 4;

    /**
     * Type of the Metric (hourly, daily, etc..)
     * 
     * @var integer
     */
    protected $type;

    /**
     * Period start
     * 
     * @var Carbon
     */
    protected $start;

    /**
     * Period End
     * 
     * @var Carbon
     */
    protected $end;

    /**
     * Embedded statistics
     * 
     * @var array
     */
    protected $statistics = [];

    /**
     * Visit count
     * 
     * @var integer
     */
    protected $count;

    public static function create(TimeInterval $interval, array $statistics, $count)
    {
        $metric = new static;
        $metric->setStart($interval->start());
        $metric->setEnd($interval->end());
        $metric->setType($interval->type());
        $metric->setStatistics($statistics);
        $metric->setCount($count);

        return $metric;
    }

    /**
     * Create a Metric object from an array
     * 
     * @param  array  $data [description]
     * @return Metric
     */
    public static function createFromArray(array $data)
    {
        $metric = new static;
        $metric->setStart($data['start']);
        $metric->setEnd($data['end']);
        $metric->setStatistics($data['statistics']);
        $metric->setCount($data['count']);
        $metric->setType($data['type']);

        return $metric;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTypeString()
    {
        $type = $this->type;

        switch($type) {
            case static::HOURLY:
                return 'Hourly';
            case static::DAILY:
                return 'Daily';
            case static::MONTHLY:
                return 'Monthly';
            case static::YEARLY:
                return 'Monthly';
        }
    }

    public function setStart(Carbon $start)
    {
        $this->start = $start;
    }

    public function setEnd(Carbon $end)
    {
        $this->end = $end;
    }

    public function setStatistics(array $statistics)
    {
        $this->statistics = $statistics;
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * Return a statistic array by key
     * @param  string $key
     * @return array
     */
    public function getStatisticsByKey($key)
    {
        if(array_key_exists($key, $this->statistics)) {
            return $this->statistics[$key];
        }
        else {
            return [];
        }

    }

    public function getCount()
    {
        return $this->count;
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'start' => $this->start,
            'end' => $this->end,
            'count' => $this->count,
            'statistics' => $this->statistics,
        ];
    }

    /**
     * String representation of metric, mostly for logging purpose
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            "%s Metrics from %s to %s",
            $this->getTypeString(),
            $this->start->toDateTimeString(),
            $this->end->toDateTimeString()
        );
    }
}
