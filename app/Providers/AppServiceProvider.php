<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    DB::connection(config('simrs.connection'))
        ->beforeExecuting(function (string $query, array $bindings, Connection $connection) {
            if (! preg_match('/^\s*(select|show|describe|explain)\b/i', $query)) {
                throw new \RuntimeException('Koneksi SIMRS hanya boleh membaca (SELECT).');
            }
        });
    }
}
