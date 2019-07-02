<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ActivateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activates a user';

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

    }
}
