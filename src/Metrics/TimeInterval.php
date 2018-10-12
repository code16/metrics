<?php

namespace Code16\Metrics;

use Carbon\Carbon;

/**
 * This is a simple value object to simplify time interval handling, and
 * preserve its integrity by only delivering copy of Carbon objects.
 */
class TimeInterval {

    /** @var Carbon */
    protected $start;

    /** @var Carbon */
    protected $end;

    protected $type;

    public function __construct(Carbon $start, Carbon $end, $type = null)
    {
        // We'll use copy() to make sure no other portion of the code
        // will modify the Carbon objects
        $this->start = $start->copy();
        $this->end = $end->copy();
        $this->type = $type;
    }

    public function type()
    {
        return $this->type;
    }

    /**
     * Return the start of the time interval
     * 
     * @return Carbon
     */
    public function start()
    {
        return $this->start->copy();
    }

    /**
     * Return the end of the time interval
     * 
     * @return Carbon
     */
    public function end()
    {
        return $this->end->copy();
    }

    /**
     * Create a new interval object for the given dates
     *
     * @param  Carbon $start
     * @param  Carbon $end
     * @param $type
     * @return TimeInterval
     */
    protected function createInterval($start, $end, $type)
    {
        return new static($start, $end, $type);
    }

    /**
     * Divide the time period
     * 
     * @return array
     */
    public function divide()
    {
        if($this->type == Metric::HOURLY) {
            return [];
        }

        return $this->divideByType($this->type);
    }

    /**
     * Spread a time interval into Hours
     * 
     * @return array
     */
    public function toHours()
    {
        return $this->divideInto(Metric::HOURLY);
    }

    /**
     * Spread a time interval into Days
     * 
     * @return array
     */
    public function toDays()
    {
        return $this->divideInto(Metric::DAILY);
    }


    /**
     * Spread a time interval into Months
     * 
     * @return array
     */
    public function toMonths()
    {
        return $this->divideInto(Metric::MONTHLY);
    }

    /**
     * Divide
     *
     * @param $type
     * @return array
     */
    protected function divideInto($type)
    {
        $offset = $this->type - $type;
        $intervals = [$this];

        for($x = 0; $x < $offset; $x++) {
            $dividedIntervals = [];
            foreach($intervals as $interval) {
                $dividedIntervals = array_merge($dividedIntervals, $interval->divide());
            }
            $intervals = $dividedIntervals;
        }

        return $intervals;
    }

    protected function divideByType($type)
    {
        $start = $this->start->copy();

        switch($type) {
            case Metric::YEARLY:
                $basePeriod = 'year';
                $dividePeriod = 'month';
                break;

            case Metric::MONTHLY:
                $basePeriod = 'month';
                $dividePeriod = 'day';
                break;

            case Metric::DAILY:
                $basePeriod = 'day';
                $dividePeriod = 'hour';
        }

        $endMethod = 'endOf'.ucfirst($dividePeriod);
        $addMethod = 'add'.ucfirst($dividePeriod);

        $base = $start->$basePeriod;

        $intervals = [];

         // We'll just dumbly add time units and create interval until the base period
         // changed, so we'll let Carbon handle different month lenghts, and timezone, 
         // DST behaviour for us.
        while($start->$basePeriod == $base) {
            
            // Compensating for the missing Carbon endOfHour() method...
            $end = ($type == Metric::DAILY)
                ? $this->endOfHour($start->copy())
                : $start->copy()->$endMethod();

            $intervals[] = new TimeInterval($start, $end, $type - 1);
            $start->$addMethod();
        }

        return $intervals;
    }

    protected function startOfHour(Carbon $date)
    {
        return $date->minute(0)->second(0);
    }

    protected function endOfHour(Carbon $date)
    {
        return $date->minute(59)->second(59);
    }

    public function getTypeString()
    {
        $type = $this->type;

        switch($type) {
            case Metric::HOURLY:
                return 'Hourly';
            case Metric::DAILY:
                return 'Daily';
            case Metric::MONTHLY:
                return 'Monthly';
            case Metric::YEARLY:
                return 'Monthly';
        }

        return 'Undefined';
    }

    /**
     * String representation of the period 
     * 
     * @return string 
     */
    public function __toString()
    {
        return sprintf(
            "%s time interval from %s to %s",
            $this->getTypeString(),
            $this->start->toDateTimeString(),
            $this->end->toDateTimeString()
        );
    }
}
