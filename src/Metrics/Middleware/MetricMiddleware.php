<?php

namespace Code16\Metrics\Middleware;

use Closure;
use Code16\Metrics\Visit;
use Code16\Metrics\Manager;
use Code16\Metrics\VisitCreator;

class MetricMiddleware
{
    /**
     * @var Manager
     */
    protected $metricManager;

    /**
     * @var VisitCreator
     */
    protected $visitCreator;

    public function __construct(Manager $metricManager, VisitCreator $creator)
    {
        $this->metricManager = $metricManager;
        $this->visitCreator = $creator;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // First we'll create the visit object, weither it will
        // be used or not, this will prevent other part of the
        // package to fails. 
        $visit = $this->visitCreator->createFromRequest($request);
        $this->metricManager->track($visit);

        // Then we implent some logic to tell if the request has
        // to be tracked or not
        
        // Handle the 'Do Not Track' header
        if($request->server('HTTP_DNT')) {
            $this->metricManager->setTrackingOff();
        }

        // In the other case, we'll only track the visit
        // if a Metric cookie already exist in the request
        if ($request->hasCookie(config('metrics.cookie_name'))) {
            $this->metricManager->setRequestCookie(true);
            $this->metricManager->setTrackingOn();
            return $next($request);
        }
        if ($request->hasCookie(config('metrics.anonymous_cookie_name'))) {
            $this->metricManager->setRequestCookie(true);
            $this->metricManager->setTrackingOn();
            return $next($request);
        }

        // If auto_place_cookie is on true, we'll log 
        // the request on every case
        if(config()->get('metrics.auto_place_cookie')) {
            $this->metricManager->setTrackingOn();
            return $next($request);
        }

        // In every other case, we'll set the tracking to off
        $this->metricManager->setTrackingOff();
        return $next($request);
    }
}
