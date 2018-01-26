<?php

namespace Code16\Metrics\Middleware;

use Closure;
use Code16\Metrics\Manager;

class NoTrackingMiddleware
{
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
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
        $this->manager->setTrackingOff();
        return $next($request);
    }
}
