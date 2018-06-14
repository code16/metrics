<?php

namespace Code16\Metrics;

use DateInterval;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Code16\Metrics\Repositories\VisitRepository;
use Illuminate\Session\SessionManager;

/**
 * Create a Visit object from a Request
 */
class VisitCreator
{
    /**
     * @var VisitRepository
     */
    protected $visits;

    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(VisitRepository $visits, Manager $manager)
    {
        $this->visits = $visits;
        $this->manager = $manager;
    }

    /**
     * Create a Visit instance from a Request object
     * 
     * @param  Request $request
     * @return Visit
     */
    public function createFromRequest(Request $request)
    {
        $visit = new Visit;
        $visit->setDate(Carbon::now());
        $visit->setUrl($request->getUri());
        $visit->setReferer($request->server('HTTP_REFERER'));
        $visit->setIp($request->ip());

        $cookiePresent = $request->hasCookie(config('metrics.cookie_name'));
        $anonCookiePresent = $request->hasCookie(config('metrics.anonymous_cookie_name'));

        if($cookiePresent) {
            $visit->setAnonymous(false);
            $cookie = $request->cookies->get(config('metrics.cookie_name'));
        }

        if($anonCookiePresent) {
            $visit->setAnonymous(true);
            $cookie = $request->cookies->get(config('metrics.anonymous_cookie_name'));
        }

        if($cookiePresent || $anonCookiePresent) {
            $visit->setCookie($cookie);
        }
        else {
            // If no cookie was found, we'll refer to config for which cookie to create
            $anonymousState = config('metrics.anonymous');
            $visit->setAnonymous($anonymousState);
            $visit->setCookie();
        }

        $visit->setUserAgent($request->server('HTTP_USER_AGENT') ? $request->server('HTTP_USER_AGENT') : 'undefined');

        if(config('metrics.enable_utm_tracking')) {
            $umtFields = $this->getUMTFromRequest($request);
            foreach($umtFields as $fieldKey => $fieldValue) {
                $visit->setCustomValue($fieldKey, $fieldValue);
            }
        }

        return $visit;
    }

    /**
     * Return utm fields from request
     * 
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    protected function getUMTFromRequest(Request $request) : array
    {
        $fields = config('metrics.utm_fields_mapping');
        
        $fieldsInRequest = [];
        
        foreach($fields as $standardUMTKey => $mappedKey) {
            if($request->has($mappedKey)) {
                $fieldsInRequest[$standardUMTKey] = $request->get($mappedKey);
            }
        }

        return $fieldsInRequest;
    }

    /**
     * Check if the cookie has expired
     * 
     * @param  string  $cookie
     * @return boolean        
     */
    protected function hasCookieExpired($cookie)
    {
        $visit = $this->visits->oldestVisitForCookie($cookie);

        if($visit) {

            $lifetime = config('metrics.cookie_lifetime');
            $date = $visit->getDate();
            $maximumLifetimeDate = Carbon::now()->sub(DateInterval::createFromDateString($lifetime));
            if($date->lt($maximumLifetimeDate)) {
                return true;
            }
        }
        return false;
    }
}