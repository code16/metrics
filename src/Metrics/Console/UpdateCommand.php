<?php

namespace Code16\Metrics\Console;

use Illuminate\Console\Command;
use Code16\Metrics\Manager;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update statistics';

    /**
     * @var Code16\Metrics\Manager
     */
    protected $manager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $updater = $this->manager->getUpdater();

        $updater->setConsole($this);
        
        $result = $updater->update();

        if($result === true) {
            $this->info('Statistics updated successfully');
        }
        else {
            $this->error('Something went wrong while updating statistics.');
        }
    }
}
