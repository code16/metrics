<?php

namespace Code16\Metrics\Repositories\Eloquent;

class MetricModel extends BaseModel
{
    protected $dates = ['start', 'end'];

    protected $table='metric_metrics';

    protected $fillable = ['start', 'end', 'count', 'statistics', 'type'];

    protected $casts = [
        'statistics' => 'array',
    ];
}
