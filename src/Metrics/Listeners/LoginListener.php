<?php

namespace Code16\Metrics\Listeners;

use Code16\Metrics\Actions\UserLoginAction;
use Code16\Metrics\Manager;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Log;

class LoginListener
{
    use DispatchesJobs;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Manager $manager, Request $request)
    {
        $this->manager = $manager;
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        if($this->manager->isRequestTracked() && ! $this->manager->visit()->isAnonymous() &&
            !$this->manager->isFiltered($this->request))
        {
            if(config('metrics.logging')) {
                $url = $this->request->url();
                Log::info('metrics : update user visits from '.$url);
            }
            // We add a user login action
            $action = new UserLoginAction($event->user->id);
            $this->manager->action($action);
            
            // Remove this feature since it leads to HUGE performance issues in some login cases

//            // Then we tell the manager to go look back in time for untracked visits
//            $job = new MarkPreviousUserVisits($event->user);
//
//            $this->dispatchSync($job);
//
//            $retro = new RetroSessions();
//            $this->dispatchSync($retro);
        }
    }

}
