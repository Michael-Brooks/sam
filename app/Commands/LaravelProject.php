<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class LaravelProject extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new:project {name}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new Laravel project in my code directory';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $argumentName = $this->argument('name');
        shell_exec('cd ~/code && composer create-project laravel/laravel ' . $argumentName);

        $this->info('Project ' . $argumentName . ' has been created!');
    }
}
