<?php

namespace Code16\Metrics\Repositories\Eloquent;

class VisitModel extends BaseModel
{
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

}
