<?php

namespace Code16\Metrics\Repositories\Eloquent;

use Carbon\Carbon;
use Code16\Metrics\Metric;
use Code16\Metrics\TimeInterval;
use Code16\Metrics\Repositories\MetricRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class MetricEloquentRepository implements MetricRepository
{
    /**
     * @var MetricModel
     */
    protected $metric;

    public function __construct()
    {
        $this->metric = new MetricModel;
    }

    /**
     * Return all metric rows
     *
     * @return Collection
     */
    public function all()
    {
        return $this->convertCollection(
            $this->metric
                ->orderBy('start','asc')
                ->get()
        );
    }

    /**
     * Return the single object corresponding to the time interval
     *
     * @param  TimeInterval $interval
     * @return  Metric
     */
    public function find(TimeInterval $interval)
    {
        $metric = $this->metric
            ->where('start', $interval->start())->where('end', $interval->end())
            ->first();

        if($metric) {
            return $this->toObject($metric);
        }

        return null;
    }

    /**
     * Check if a metric exists in DB
     *
     * @param  TimeInterval $interval
     * @return boolean
     */
    public function has(TimeInterval $interval)
    {
        $count = $this->metric
            ->where('start', $interval->start())
            ->where('end', $interval->end())
            ->count();

        return $count > 0;
    }

    /**
     * Return first metric start
     *
     * @return Carbon | null
     */
    public function getMinStart()
    {
        if($result = $this->metric->selectRaw("min(start) as min_start")->first()) {
            return $result->min_start ? Carbon::parse($result->min_start) : null;
        }

        return null;
    }

    /**
     * Return all the metric records for the given time interval
     *
     * @param  TimeInterval $interval
     * @return Collection
     */
    public function getTimeInterval(TimeInterval $interval)
    {
        $metrics = $this->metric
            ->where('start', '>=', $interval->start())
            ->where('end', '<', $interval->end())
            ->orderBy('start','asc')
            ->get();

        return $this->convertCollection($metrics);
    }

    /**
     * Return all the metric records for the given time interval & type
     *
     * @param  TimeInterval $interval
     * @param  integer  $type
     * @return Collection
     */
    public function getTimeIntervalByType(TimeInterval $interval, $type)
    {
        $metrics = $this->metric->whereType($type)
            ->where('start', '>=', $interval->start())
            ->where('end', '<=', $interval->end())
            ->orderBy('start','asc')
            ->get();

        return $this->convertCollection($metrics);
    }

    /**
     * Return true if a time interval exists
     *
     * @param  TimeInterval $interval
     * @return boolean
     */
    public function hasTimeInterval(TimeInterval $interval)
    {
        $metric = $this->find($interval);

        return $metric ? true : false;
    }

    /**
     * Store a Metric object
     *
     * @param  Metric $metric
     */
    public function store(Metric $metric)
    {
        MetricModel::create($metric->toArray());
    }

    /**
     * Convert an Eloquent Collection into a standard Collection of Visit objects
     *
     * @param EloquentCollection $collection
     * @return \Illuminate\Support\Collection
     */
    protected function convertCollection(EloquentCollection $collection)
    {
        $baseCollection = $collection->toBase();

        return $baseCollection->transform(function ($item, $key) {
            return Metric::createFromArray($item->toArray() );
        });
    }

    protected function toObject(MetricModel $model)
    {
        return Metric::createFromArray($model->toArray());
    }
}
