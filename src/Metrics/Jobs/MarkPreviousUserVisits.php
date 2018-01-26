<?php

namespace Code16\Metrics\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Code16\Metrics\Manager;

class MarkPreviousUserVisits implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param mixed User
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Mark previous user visits
     * 
     * @return void
     */
    public function handle(Manager $manager)
    {
        $manager->markPreviousUserVisits($this->user->id);
    }
}
