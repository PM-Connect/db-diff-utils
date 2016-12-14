<?php

namespace PMConnect\DBDiff\Utils;

use Illuminate\Support\ServiceProvider;
use PMConnect\DBDiff\Utils\Console\Commands\DiffDatabase;

class DBDiffUtilServiceProvider extends ServiceProvider
{
    /**
     * Setup the database diff command for Laravel.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            DiffDatabase::class,
        ]);
    }

    public function register()
    {
        //
    }
}
