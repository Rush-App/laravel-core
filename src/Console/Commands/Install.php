<?php

namespace RushApp\Core\Console\Commands;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install requirements and publish files.';

    protected $progressBar;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->progressBar = $this->output->createProgressBar();

        $this->progressBar->start();

        $this->info(' Core installation started. Please wait...');
        $this->progressBar->advance();

        $this->line(' Publishing configs, views, js and css files');
        $this->executeArtisanProcess('vendor:publish', [
            '--provider' => 'RushApp\Core\CoreServiceProvider',
            '--tag' => 'minimum',
        ]);


        return 0;
    }
}
