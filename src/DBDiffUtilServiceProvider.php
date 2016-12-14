<?php

namespace PMConnect\DBDiff\Utils;

use Illuminate\Support\ServiceProvider;
use PMConnect\DBDiff\Utils\Console\Commands\DiffDatabase;

class DFDiffUtilServiceProvider extends ServiceProvider
{
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
