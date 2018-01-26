<?php

namespace Code16\Metrics\Analyzers;

use Carbon\Carbon;
use Code16\Metrics\Visit;
use Code16\Metrics\Metric;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Collection;
use Code16\Metrics\Repositories\VisitRepository;
use Code16\Metrics\TimeInterval;

class VisitorAnalyzer extends Analyzer
{
    /**
     * @var VisitRepositoty
     */
    protected $visits;

    /**
     * Session lifetime, in minutes
     * 
     * @var integer
     */
    protected $lifetime;

    /**
     * UserAgent library
     * 
     * @var Agent
     */
    protected $agent;

    public function __construct(VisitRepository $visits)
    {
        $this->visits = $visits;
        $this->lifetime = config('session.lifetime');
        $this->agent = new Agent();
    }

    /**
     * Compile visits into array of statistics
     * 
     * @param  Collection $visits
     * @return array
     */
    public function compile(Collection $visits, TimeInterval $interval)
    {
        $count = 0;
        $sessionStack = [];

        foreach($visits as $visit) {

            if($this->isRobot($visit)) {
                continue;
            }

            $sessionId = $visit->getSessionId();
            
            if(! in_array($sessionId, $sessionStack)) {
                if(! $this->hasPreviousSession($sessionId, $visit->getDate())) {
                    $count++;
                }
                $sessionStack[] = $sessionId;
            }
        }
        
        return ['visitors' => $count];
    }

    /**
     * Check if visit is a robot, from its user agent
     * 
     * @param  Visit   $visit 
     * @return boolean        
     */
    protected function isRobot(Visit $visit)
    {
        $this->agent->setUserAgent($visit->getUserAgent());
        return $this->agent->isRobot();
    }

    /**
     * Consolidate statistics
     * 
     * @param  Collection $metrics 
     * @return array
     */
    public function consolidate(Collection $metrics, TimeInterval $interval)
    {
        $count = 0;

        foreach($metrics as $metric) {
            $statistic = $metric->getStatisticsByKey(get_class($this));
            if(array_key_exists('visitors', $statistic)) {
                $count+= $statistic['visitors'];
            }
        }
        
        return ['visitors' => $count];
    }

    /**
     * Chech if visitor has already a previous session
     * 
     * @param  string  $sessionId 
     * @param  Carbon  $visitDate 
     * @return boolean            
     */
    protected function hasPreviousSession($sessionId, Carbon $visitDate)
    {
        $from = $visitDate->subMinutes($this->lifetime);
        
        $visit = $this->visits->lastVisitBySession($sessionId, $from);

        return $visit ? false : true;
    }

}
