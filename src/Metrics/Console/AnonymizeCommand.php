<?php

namespace Code16\Metrics\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Code16\Metrics\Repositories\VisitRepository;

class AnonymizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:anonymize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean references to user_id in visits older than 12 months';

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
        $date = Carbon::now()->subYears(1);

        $this->visits->anonymizeUntil($date);
    }
}
