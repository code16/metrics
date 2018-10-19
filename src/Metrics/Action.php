<?php

namespace Code16\Metrics;

abstract class Action
{
    /** @var array */
    public $utmFields = [];

    /**
     * Grab UTM fields from session, if any
     */
    protected function addUtmFields()
    {
        $this->utmFields = session('metrics.utm_fields', []);
    }
}