<?php

namespace Code16\Metrics\Middleware;

use Closure;
use Code16\Metrics\Manager;
use Code16\Metrics\VisitCreator;
use DateInterval;
use Illuminate\Support\Str;

class SetCookieMiddleware
{
    /**
     * @var Manager
     */
    protected $metricManager;

    /**
     * @var VisitCreator
     */
    protected $visitCreator;

    public function __construct(Manager $metricManager, VisitCreator $visitCreator)
    {
        $this->metricManager = $metricManager;
        $this->visitCreator = $visitCreator;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if(! config('metrics.enable')) {
            return $response;
        }

        if($this->metricManager->hasPlaceDntCookie() || !$this->metricManager->isRequestTracked()) {
            $this->doNotTrack($response);

            return $response;
        }

        $response->headers->setCookie(
            cookie()->forget(config('metrics.do_not_track_cookie_name'))
        );

        $visit = $this->metricManager->visit();

        // Putting code for session here as we're pretty
        // sure it session middleware will be started then
        if(session()->has('metrics_session_id')) {
            $visit->setSessionId(session()->get('metrics_session_id'));
        }
        else {
            $sessionId = Str::random(16);
            $visit->setSessionId($sessionId);
            session()->put('metrics_session_id', $sessionId);
        }

        $value = $visit->getCookie();
        $cookieName = config('metrics.cookie_name');
        $anonCookieName = config('metrics.anonymous_cookie_name');

        if($visit->isAnonymous()) {
            $response->headers->setCookie(cookie()->make($anonCookieName, $value, $this->getLifetime()));
            $response->headers->setCookie(cookie()->forget($cookieName));
        }
        else {
            $response->headers->setCookie(cookie()->make($cookieName, $value, $this->getLifetime()));
            $response->headers->setCookie(cookie()->forget($anonCookieName));
        }

        return $response;
    }

    /**
     * Get lifetime, in minutes
     *
     * @return integer
     */
    protected function getLifetime()
    {
        $lifetime = config('metrics.cookie_lifetime');

        $dateInterval = DateInterval::createFromDateString($lifetime);

        $seconds = date_create('@0')->add($dateInterval)->getTimestamp();

        return $seconds * 60;
    }

    protected function doNotTrack($response)
    {
        $response->headers->setCookie(cookie()->forget(config('metrics.cookie_name')));
        $response->headers->setCookie(cookie()->forget(config('metrics.anonymous_cookie_name')));

        if($this->metricManager->hasPlaceDntCookie()) {
            $response->headers->setCookie(cookie()->make(config('metrics.do_not_track_cookie_name'), true, $this->getLifetime()));
        }
    }
}
