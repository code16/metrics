<?php

namespace Code16\Metrics\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;

class VisitModel extends Model
{
    use PreserveDateAsCarbonTrait;
    
    public $timestamps = false;

    protected $table='metric_visits';
    
    protected $fillable = [
        'ip', 
        'user_agent', 
        'user_id', 
        'custom', 
        'actions', 
        'date', 
        'url', 
        'cookie',
        'referer', 
        'anonymous',
        'session_id',
    ];

    protected $casts = [
        'actions' => 'array',
        'custom' => 'array',
    ];

    protected $dates = [
        'date',
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
