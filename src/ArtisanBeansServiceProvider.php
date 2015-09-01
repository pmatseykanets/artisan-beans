<?php

namespace Pvm\ArtisanBeans;

use Illuminate\Support\ServiceProvider;

class ArtisanBeansServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $commands = [
            'Bury',
            'Delete',
            'Kick',
            'PauseTube',
            'Peek',
            'Purge',
            'Put',
            'ServerStats',
            'TubeStats',
            'UnpauseTube',
        ];

        foreach ($commands as $command) {
            $this->registerCommand($command);
        }
    }

    /**
     * Register a console command.
     *
     * @param $command
     */
    protected function registerCommand($command)
    {
        $abstract = "command.artisan.beans.$command";
        $commandClass = "\\Pvm\\ArtisanBeans\\Console\\{$command}Command";

        $this->app->singleton($abstract, function ($app) use ($commandClass) {
            return new $commandClass();
        });

        $this->commands($abstract);
    }
}
