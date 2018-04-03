<?php

namespace Code16\Metrics;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Code16\Metrics\Exceptions\MetricException;
use Code16\Metrics\Repositories\MetricRepository;
use Code16\Metrics\Repositories\VisitRepository;

class Manager
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * @var Visit
     */
    protected $visit;

    /**
     * @var boolean
     */
    protected $trackRequest = false;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $analyzers = [];

    /**
     * @var array
     */
    protected $consoliders = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $pendingActions = [];

    /**
     * @var boolean
     */
    protected $anonymousRequest = false;

    /**
     * @var boolean
     */
    protected $requestHasCookie = false;

    /**
     * @var boolean
     */
    protected $hasPlaceDntCookie = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Generate metrics for all due periods, if they don't exist yet
     *
     * @return bool
     * @throws MetricException
     */
    public function updateMetrics()
    {
        return $this->getUpdater()->update();
    }

    /**
     * Instantiate & Return Updater
     *
     * @return Updater
     * @throws MetricException
     */
    public function getUpdater()
    {
        return new Updater(
            $this->app->make(MetricRepository::class),
            $this->app->make(VisitRepository::class),
            $this->instantiateProcessors($this->getAnalyzersFromConfig()),
            $this->instantiateProcessors($this->getConsolidersFromConfig())
        );
    }

    /**
     * Set the tracking object
     *
     * @param  Visit  $visit [description]
     * @return Manager
     */
    public function track(Visit $visit)
    {
        if($this->visit != null) {
            //throw new TrackingException('Tracking object $visit cannot be set twice');
            return $this;
        }

        $this->visit = $visit;

        foreach($this->pendingActions as $action) {
            $this->visit->addAction($action);
        }

        return $this;
    }

    /**
     * Access the current visit
     *
     * @return Visit
     */
    public function visit()
    {
        return $this->visit;
    }

    /**
     * Attach an action to the current visit
     *
     * @param  Action $action
     * @return void
     */
    public function action(Action $action)
    {
        if($this->visit != null) {
            $this->visit->addAction($action);
        }
        else {
            // In some cases (eg Middleware), the current Visit will
            // not be already initialized. So we'll stack it into
            // an array and add them when initialized.
            $this->pendingActions[] = $action;
        }
    }

    /**
     * Flag to tell if the request has the cookie originally set
     *
     * @param boolean $cookie
     * @return void
     */
    public function setRequestCookie($cookie = true)
    {
        $this->requestHasCookie = $cookie;
    }

    /**
     * Return true if a metrics cookie was originally found in the request
     *
     * @return boolean
     */
    public function isCookieInRequest()
    {
        return $this->requestHasCookie;
    }

    /**
     * Indicate if the tracking is enabled for the current request
     *
     * @return boolean
     */
    public function isRequestTracked()
    {
        return $this->trackRequest;
    }

    /**
     * Disable tracking for current request
     *
     * @return void
     */
    public function setTrackingOff()
    {
        $this->trackRequest = false;
    }

    /**
     * Disable tracking for current request
     *
     * @return void
     */
    public function setTrackingOn()
    {
        $this->trackRequest = true;
    }

    /**
     * When this method is called, the cookie that will be placed
     * on the user's browser will not be attached to its user_id
     *
     * @return void
     */
    public function setAnonymous($anonymous = true)
    {
        if($anonymous) {
            $visit = $this->visit();

            // If the visit is not in anonymous state, it means
            // either it's a first visit, or the request had
            // a previous cookie which wasn't anonymous. In that
            // case, we'll generate a new one.
            if($visit && ! $visit->isAnonymous()) {
                $visit->setAnonymous(true);
                $visit->setCookie();
            }
        }
        else {
            $visit = $this->visit();
            if($visit && $visit->isAnonymous()) {
                $visit->setAnonymous(false);
                $visit->setCookie();
            }
        }
    }

    /**
     * Return anonymous state of the request
     *
     * @return boolean|null
     */
    public function isAnonymous()
    {
        if(! $this->visit()) {
            return null;
        }
        return $this->visit()->isAnonymous();
    }

    /**
     * Look into past for the indicated user
     *
     * @param  string  $userId
     * @return boolean
     */
    public function markPreviousUserVisits($userId)
    {
        if($this->isRequestTracked() )
        {
            $timeMachine = $this->getTimeMachine();
            return $timeMachine->lookup($userId);
        }
        else {
            return false;
        }
    }

    /**
     * Update previous visitor session with newly set session id
     *
     * @return boolean
     */
    public function updatePreviousSessions()
    {
        if($this->isRequestTracked() )
        {
            $timeMachine = $this->getTimeMachine();
            return $timeMachine->updatePreviousSessions();
        }
        else {
            return false;
        }
    }

    /**
     * Get the time machine instance
     *
     * @return TimeMachine
     */
    protected function getTimeMachine()
    {
        $timeMachine = $this->app->make(TimeMachine::class);
        $timeMachine->setCurrentVisit($this->visit());
        return $timeMachine;
    }

    /**
     * Add a custom data provider which will provide the Visit object
     * with a custom field at each request
     *
     * @param Closure | class $callback
     * @return void
     */
    public function addDataProvider($callback)
    {
        $this->providers[] = $callback;
    }

    /**
     * Parse providers and execute them on visit instance
     *
     * @return void
     */
    public function processDataProviders()
    {
        foreach($this->providers as $provider) {
            if($provider instanceof Closure) {
                $visit = $this->visit;
                $provider($visit);
            }
            else {
                $provider = $this->app->make($provider);
                $provider->process($this->visit);
            }

        }
    }

    /**
     * Return the analyzers classes from config
     *
     * @return array
     */
    protected function getAnalyzersFromConfig()
    {
        return $this->app['config']->get('metrics.analyzers');
    }

    /**
     * Return the consoliders classes from config
     *
     * @return array
     */
    protected function getConsolidersFromConfig()
    {
        return $this->app['config']->get('metrics.consoliders');
    }

    /**
     * Convert processors class name into object instances
     *
     * @param array $processorConfig
     * @return array
     * @throws MetricException
     */
    protected function instantiateProcessors(array $processorConfig)
    {
        $processors = [];

        foreach($processorConfig as $period => $classes) {
            $periodValue = $this->getPeriodConstantFromString($period);
            $processors[$periodValue] = [];
            foreach($classes as $class) {
                $processors[$periodValue][] =  $this->app->make($class);
            }
        }

        return $processors;
    }

    /**
     * @param $period
     * @return int
     * @throws MetricException
     */
    protected function getPeriodConstantFromString($period)
    {
        switch($period) {
            case 'hourly':
                return Metric::HOURLY;
            case 'daily':
                return Metric::DAILY;
            case 'monthly':
                return Metric::MONTHLY;
            case 'yearly':
                return Metric::YEARLY;
        }

        throw new MetricException("Invalid period in config : $period");
    }

    /**
     * Return true if the URL should not be logged as of user configuration
     * or user defined filter
     *
     * @param  Request $request
     * @return boolean
     */
    public function isFiltered(Request $request)
    {
        if($this->isFilteredInConfig($request)) {
            return true;
        }

        $visit = $this->visit;

        foreach($this->filters as $filter) {
            if ($filter($visit) == true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return true if the Requested url is filtered in metrics config
     *
     * @param  Request $request
     * @return boolean
     */
    protected function isFilteredInConfig(Request $request)
    {
        $filteredUrls = $this->app->make('config')->get('metrics.filtered_urls');

        if(is_array($filteredUrls)) {
            return call_user_func_array([$request, 'is'], $filteredUrls);
        }
        else {
            return false;
        }
    }

    /**
     * Add a function to which will be passed the current visit,
     * right before being saved.
     *
     * @param  Closure $filter
     * @return  void
     */
    public function addFilter(Closure $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Return true if we need to place a do_not_track cookie at the
     * end of the request
     *
     * @return boolean
     */
    public function hasPlaceDntCookie()
    {
        return $this->hasPlaceDntCookie;
    }

    /**
     * Call this to place a Do_not_track cookie
     *
     * @return void
     */
    public function placeDntCookie()
    {
        $this->hasPlaceDntCookie = true;
    }

    /**
     * Call this to place a Do_not_track cookie
     *
     * @return void
     */
    public function removeDntCookie()
    {
        $this->hasPlaceDntCookie = false;
        $this->setTrackingOn();
    }
}
