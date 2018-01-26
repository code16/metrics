<?php

namespace Code16\Metrics\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;

class MetricModel extends Model
{
    use PreserveDateAsCarbonTrait;
    
    public $timestamps = false;

    protected $dates = ['start', 'end'];

    protected $table='metric_metrics';

    protected $fillable = ['start', 'end', 'count', 'statistics', 'type'];

    protected $casts = [
        'statistics' => 'array',
    ];
    
    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('metrics.connection');
    }
}
