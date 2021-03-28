<?php

namespace RushApp\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->output->progressStart(3);

        $this->info(' Core installation started. Please wait...');
        $this->output->progressAdvance(2);

        $this->line(' Publishing configs, views, js and css files');
        $this->call('vendor:publish', [
            '--provider' => 'RushApp\Core\CoreServiceProvider',
            '--tag' => 'minimum',
        ]);

        $this->output->progressAdvance(3);
        $this->cacheClear();
        $this->output->progressFinish();

        return 0;
    }

    private function cacheClear()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:cache');
    }
}
