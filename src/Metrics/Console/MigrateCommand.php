<?php

namespace Code16\Metrics\Console;

use Illuminate\Console\Command;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:migrate {--refresh : if present existing tables will be deleted first} {--base_dir=metrics : metrics base bir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate metrics database tables.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option("refresh")) {
            $this->call('migrate:refresh', [
                "--path" => $this->option("base_dir") . "/src/database/migrations"
            ]);
        } else {
            $this->call('migrate', [
                "--path" => $this->option("base_dir") . "/src/database/migrations",
                "--force" => true
            ]);
        }
    }
}
