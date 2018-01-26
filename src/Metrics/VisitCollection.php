<?php

namespace Code16\Metrics;

use Illuminate\Support\Collection;

class VisitCollection extends Collection
{

    /**
     * Filter the collection with visits containing the given action type
     * 
     * @param  string $type action class name
     * @return  VisitCollection
     */
    public function withAction($type)
    {
        return $this->filter(function($visit) use($type) {
            return $visit->hasAction($type);
        });
    }

}