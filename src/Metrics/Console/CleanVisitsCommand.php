<?php

namespace Code16\Metrics\Console;

use Carbon\Carbon;
use Code16\Metrics\Repositories\VisitRepository;
use DateInterval;
use Illuminate\Console\Command;
use Code16\Metrics\Manager;

class CleanVisitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:clean-visits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean visits';

    /**
     * @var VisitRepository
     */
    protected $visits;

    /**
     * @param VisitRepository $visits
     */
    public function __construct(VisitRepository $visits)
    {
        parent::__construct();

        $this->visits = $visits;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->visits->deleteUntil(
            Carbon::now()->sub(
                DateInterval::createFromDateString(config('metrics.visits_retention_time'))
            )
        );
    }
}
