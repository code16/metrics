<?php

namespace Code16\Metrics\Repositories\Eloquent;

trait PreserveDateAsCarbonTrait {

    public function toArray()
    {
        $attributes = parent::toArray();
        foreach($this->dates as $date)
        {
            $attributes[$date] = $this->asDateTime($attributes[$date]);
        }
        return $attributes;
    }

}
