<?php

namespace Code16\Metrics\Middleware;

use Closure;
use Code16\Metrics\Manager;
use Code16\Metrics\Repositories\VisitRepository;
use Illuminate\Contracts\Auth\Guard;

class StoreMetricMiddleware
{
    /**
     * @var VisitRepository
     */
    protected $visits;

    /**
     * @var Manager
     */
    protected $metricManager;

    /**
     * @var Guard
     */
    protected $guard;

    public function __construct(VisitRepository $visits, Manager $metricManager, Guard $guard)
    {
        $this->visits = $visits;
        $this->metricManager = $metricManager;
        $this->guard = $guard;
    }   

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if(! config('metrics.enable')) {
            return;
        }

        $visit = $this->metricManager->visit();

        // We'll add the status code there, making sure that response status isn't modified
        // in any other middleware. 
        if($visit) {
            $visit->setStatusCode($response->getStatusCode());
        }

        if($visit && $this->metricManager->isRequestTracked() && ! $this->metricManager->isFiltered($request)) {
            // As some authentication method will take place after the middleware are executed, we'll wait
            // for this last moment to set the user id, if present.
            if(! $visit->isAnonymous() && $this->guard->user()) {
                $visit->setUserId($this->guard->user()->id);
            }

            $this->metricManager->processDataProviders();
            
            $this->visits->store($this->metricManager->visit());    
        }
    }
}
