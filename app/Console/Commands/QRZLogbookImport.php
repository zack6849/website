<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class QRZLogbookImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logbook:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import logbook from QRZ.com via XML subscriber API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Syncing logbook from QRZ.com");
        dispatch_sync(resolve(\App\Jobs\Logbook\QRZLogbookImport::class));
        $this->info("Sync complete");
        return CommandAlias::SUCCESS;
    }
}
