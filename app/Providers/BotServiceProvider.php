<?php

namespace App\Providers;

use Laracord\LaracordServiceProvider;

class BotServiceProvider extends LaracordServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if (env('APP_ENV') === 'development') {
            \DB::listen(function ($query) {
                $sql = $query->sql;
                $bindings = json_encode($query->bindings);
                $time = $query->time;
                $log = '['.date('Y-m-d H:i:s')."] SQL: $sql | Bindings: $bindings | Time: {$time}ms\n";
                $logPath = storage_path('logs/query.log');
                file_put_contents($logPath, $log, FILE_APPEND);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }
}
