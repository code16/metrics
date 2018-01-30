<?php

namespace Code16\Metrics\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use PreserveDateAsCarbonTrait;
    
    public $timestamps = false;

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
