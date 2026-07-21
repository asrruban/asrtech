<?php

namespace App\Providers;

use App\Console\Commands\CreateAdminCommand;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([CreateAdminCommand::class]);
    }
}
