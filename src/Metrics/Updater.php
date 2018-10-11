<?php

namespace Code16\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Code16\Metrics\Repositories\MetricRepository;
use Code16\Metrics\Repositories\VisitRepository;
use Code16\WriteToConsole\WriteToConsole;

class Updater 
{
    use WriteToConsole;

    /**
     * @var MetricRepository
     */
    protected $metrics;

    /**
     * @var VisitRepository
     */
    protected $visits;

    /**
     * @var array
     */
    protected $analyzers;

    /**
     * @var array
     */
    protected $consoliders;

    /**
     * @param MetricRepository $metrics
     * @param VisitRepository $visits
     * @param array $analyzers
     * @param array $consoliders
     */
    public function __construct(MetricRepository $metrics, VisitRepository $visits, array $analyzers, array $consoliders)
    {
        $this->metrics = $metrics;
        $this->visits = $visits;
        $this->analyzers = $analyzers;
        $this->consoliders = $consoliders;
    }

    /**
     * Update the metrics. Return true if update has been processed,
     * false if there is no data to process.
     * 
     * @return boolean
     */
    public function update()
    {
        $start = $this->getPeriodStart();
        $end = $this->getPeriodEnd();

        if($start && $end) {
            return $this->processUpdate($start, $end);
        }

        return false;
    }

    /**
     * Process update operation
     * 
     * @param  Carbon $start 
     * @param  Carbon $end
     * @return boolean
     */
    protected function processUpdate(Carbon $start, Carbon $end)
    {
        $this->info('Analyzing Visits...');

        // First, we'll get the complete period in the timeframe, with only the 
        // top level periods (meaning we'll only have the Year TimeInterval of a complete
        // year, not the month TimeInterval the years is composed)
        $completePeriods = $this->getCompletePeriods($start, $end);

        // Then, we'll pipe them to a method that will recursively check into complete
        // periods to find potentially missing smaller units. If a larger unit is present,
        // it will assume the smaller units are present as well. 
        $missingPeriods = new Collection($this->parseForMissingMetrics($completePeriods));

        if($missingPeriods) {
            // Now, we'll have to sort them by type as we want to process the smaller unit first.
            $sortedPeriods = $missingPeriods->sortBy(function ($item, $key) {
                return $item->type();
            });

            foreach ($sortedPeriods as $period) {
                // Process will return false if there was no data to handle in a given period.
                $metric = $this->process($period);
                
                if($metric) {
                    $this->metrics->store($metric);
                    $this->info("Analyzed & Stored : $metric");

                } else {
                    $this->warning("No data for period : $period");
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Process  a metric for a given period
     * 
     * @param  TimeInterval $period 
     * @return  Metric|null
     */
    protected function process(TimeInterval $period)
    {
        $metric = $this->processAnalyze($period);

        return ($period->type() == Metric::HOURLY)
            ? $metric
            : $this->processConsolidate($period, $metric);
    }

    /**
     * Check if analyzers are present for a given period
     * 
     * @param  integer  $periodType
     * @return boolean             
     */
    protected function hasAnalyzers($periodType)
    {
        return count($this->analyzers[$periodType]) > 0 ? true : false;
    }

    /**
     * Process Analyze and output a metric
     * 
     * @param  TimeInterval $period
     * @return Metric
     */
    protected function processAnalyze(TimeInterval $period)
    {
        // Check if there are analyzers setUp for a given period, if not
        // we'll don't waste memory by just initializing an empty Metric 
        // instance, and pass it over to consolidate
        
        if($this->hasAnalyzers($period->type())) {
            $compiler = new Compiler($this->analyzers[$period->type()]);
            $visits = $this->visits->getTimeInterval($period->start(), $period->end());

            if (count($visits) > 0) {
                $statistics = $compiler->compile($visits, $period);
                return Metric::create($period, $statistics, count($visits));
            }
        }

        return Metric::create($period, [], 0);
    }

    /**
     * Process Consolidate for a given time period
     *
     * @param  TimeInterval  $period
     * @param  Metric $metric
     * @return Metric | null
     */
    protected function processConsolidate(TimeInterval $period, Metric $metric)
    {
        if(!$metrics = $this->metrics->getTimeIntervalByType($period, $period->type() - 1)) {
            return null;
        }

        $metric->setCount($metrics->reduce(function ($carrier, $metric) {
            return $carrier + $metric->getCount();
        }, 0));

        $consolider = new Consolider($this->consoliders[$period->type()]);
        $statistics = $consolider->consolidate($metrics, $period);

        $metric->setStatistics(array_merge($metric->getStatistics(), $statistics));

        return $metric;
    }

    /**
     * Parse for metrics that are not in DB for a Period. 
     * 
     * @param array $periods
     * @return Collection
     */
    public function parseForMissingMetrics($periods)
    {
        $missingMetrics = [];
        
        foreach($periods as $period) {
            if(! $this->metrics->has($period)) {
                
                // We'll check that there are visits in that period
                // so we don't divide into smaller units if there
                // are no visits in the first place.
                if($this->visits->countByTimeInterval($period) == 0) {
                    continue;
                }

                $missingMetrics[] = $period;
                if($period->type() != Metric::HOURLY) {
                    $missingMetrics = array_merge($missingMetrics, $this->parseForMissingMetrics($period->divide()));
                }
            }
        }

        return $missingMetrics;
    }

    /**
     * Return the completed years, months, day, hours
     * 
     * @param  Carbon $start 
     * @param  Carbon $end  
     * @return  Collection
     */
    public function getCompletePeriods(Carbon $start, Carbon $end)
    {
        $periods = [];

        $periods = array_merge($periods, $this->getCompletePeriodsByType($start, $end, Metric::YEARLY));
        $start = $end->copy()->startOfYear();
        $periods = array_merge($periods, $this->getCompletePeriodsByType($start, $end, Metric::MONTHLY));
        $start = $end->copy()->startOfMonth();
        $periods = array_merge($periods, $this->getCompletePeriodsByType($start, $end, Metric::DAILY));
        $start = $end->copy()->startOfDay();
        $periods = array_merge($periods, $this->getCompletePeriodsByType($start, $end, Metric::HOURLY));
        
        return new Collection($periods);
    }

    /**
     * Get top-level complete periods for the given $start & $end
     * 
     * @param  Carbon $start
     * @param  Carbon $end  
     * @param  integer $type  
     * @return  array
     */
    protected function getCompletePeriodsByType(Carbon $start, Carbon $end, $type)
    {
        switch($type) {
            case Metric::YEARLY:
                $diff = $end->diffInYears($start);
                break;
            case Metric::MONTHLY:
                $diff = $end->diffInMonths($start);
                break;
            case Metric::DAILY:
                $diff = $end->diffInDays($start);
                break;
            case Metric::HOURLY:
                $diff = $end->diffInHours($start);
        }
       
        $intervals = [];

        for($x = 0; $x < $diff; $x++) {

            switch ($type) {
                case Metric::YEARLY:
                    $intervalStart = $start->copy()->addYears($x)->startOfYear();
                    $intervalEnd = $intervalStart->copy()->endOfYear();
                    break;
                case Metric::MONTHLY:
                    $intervalStart = $start->copy()->addMonths($x)->startOfMonth();
                    $intervalEnd = $intervalStart->copy()->endOfMonth();
                    break;
                case Metric::DAILY:
                    $intervalStart = $start->copy()->addDays($x)->startOfDay();
                    $intervalEnd = $intervalStart->copy()->endOfDay();
                    break;
                case Metric::HOURLY:
                    $intervalStart = $start->copy()->addHours($x)->minute(0)->second(0);
                    $intervalEnd = $intervalStart->copy()->minute(59)->second(59);
            }
            
            $intervals[] = new TimeInterval($intervalStart, $intervalEnd, $type);    
        }

        return $intervals;
    }

    /**
     * Get the start date for the Metric processing period
     * 
     * @return Carbon
     */
    public function getPeriodStart()
    {
        // First we'll check if metrics exists, and if so we'll make the first metric in time
        // the start of our reference period. 
        if($firstMetric = $this->metrics->first()) {
            $start = $firstMetric->getStart();

            return $start->startOfYear();

        } elseif($start = $this->visits->first()) {
            return $start->getDate()->startOfYear();
        }

        return null;
    }

    /**
     * Get the end of the processing period, which will be always the end of the last
     * hour.
     * 
     * @return Carbon
     */
    public function getPeriodEnd()
    {
        return Carbon::now()->subHour()->minute(59)->second(59);
    }
}
