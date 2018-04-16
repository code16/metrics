<?php

namespace Code16\Metrics\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Code16\Metrics\Manager;

class UpdateCommand extends Command
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
     * @var Code16\Metrics\Repositories\VisitRepository
     */
    protected $visits;

    public function __construct(VisitRepository $visits)
    {
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
