<?php

namespace App\Console\Commands;

use App\Jobs\DatabaseTransferJob;
use Illuminate\Console\Command;

class MigrationTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrationjob:do';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do Topup package group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DatabaseTransferJob::dispatch() ;
    }
}
