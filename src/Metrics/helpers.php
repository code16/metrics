<?php

use Code16\Metrics\Action;
use Code16\Metrics\Manager;

if (! function_exists('metrics')) {
    
    /**
     * Get the Metrics Manager instance
     *
     * @return Code16\Metrics\Manager
     */
    function metrics()
    {
        return app(Manager::class);
    }
}

if (! function_exists('metrics_action')) {
    
    /**
     * Attach an action object to the current visit
     *
     * @return void
     */
    function metrics_action(Action $action)
    {
        return app(Manager::class)->action($action);
    }
}

if (! function_exists('metrics_has_cookie')) {
    
    /**
     * Return true if the request had originally a metrics cookie in it
     *
     * @return boolean
     */
    function metrics_has_cookie()
    {
        return app(Manager::class)->isCookieInRequest();
    }
}

if (! function_exists('metrics_is_anonymous')) {
    
    /**
     * Return true if the request had originally a metrics cookie in it
     *
     * @return boolean
     */
    function metrics_is_anonymous()
    {
        return app(Manager::class)->isAnonymous();
    }
}



