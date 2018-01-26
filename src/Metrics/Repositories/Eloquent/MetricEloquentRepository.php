<?php

namespace Code16\Metrics\Repositories\Eloquent;

use Code16\Metrics\Metric;
use Code16\Metrics\TimeInterval;
use Code16\Metrics\Repositories\MetricRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
        return $this->convertCollection($this->metric->all());
    }

    /**
     * Return the single object corresponding to the time interval
     *
     * @param  TimeInterval $interval
     * @return  Metric
     */
    public function find(TimeInterval $interval)
    {
        $metric = $this->metric->where('start', $interval->start())->where('end', $interval->end())->first();

        if($metric) {
            return $this->toObject($metric);
        }
        else {
            return null;
        }
    }

    /** 
     * Check if a metric exists in DB
     * 
     * @param  TimeInterval $interval
     * @return boolean               
     */
    public function has(TimeInterval $interval)
    {
        $count = $this->metric->where('start', $interval->start())->where('end', $interval->end())->count();

        return $count > 0;
    }

    /**
     * Return first metric
     * 
     * @return Metric 
     */
    public function first()
    {
        $metric = $this->metric->orderBy('start', 'asc')->first();

        if($metric) {
            return $this->toObject($metric);
        }
        else {
            return null;
        }
    }

    /**
     * Return all the metric records for the given time interval
     * 
     * @param  TimeInterval $interval
     * @return Collection
     */
    public function getTimeInterval(TimeInterval $interval)
    {
        $metrics = $this->metric->where('start', '>=', $interval->start())->where('end', '<', $interval->end())->get();
        
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
            ->where('end', '<=', $interval->end())->get();
        
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
     * @param  Metric $metric 
     * @return  void
     */
    public function store(Metric $metric)
    {
        $attributes = $metric->toArray();

        if(isset($attributes['id']) && $attributes['id'] !== null) {
            return $this->saveExisting($attributes);
        }
        else {
            unset($attributes['id']);
        }

        $metric = MetricModel::create($attributes);
    }

    /**
     * Convert an Eloquent Collection into a standard Collection of Visit objects
     * 
     * @return Collection
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
        return Metric::createFromArray($model->toArray() );
    }
}
