<?php

namespace ZalaNihir\DeadcodeScanner;

use Illuminate\Support\ServiceProvider;
use ZalaNihir\DeadcodeScanner\Commands\ScanDeadCode;

class DeadCodeScannerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/deadcode.php', 'deadcode');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/deadcode.php' => config_path('deadcode.php'),
            ], 'config');

            // Dedicated publish tag for this package's config
            $this->publishes([
                __DIR__ . '/config/deadcode.php' => config_path('deadcode.php'),
            ], 'deadcode-config');

            $this->commands([
                ScanDeadCode::class,
            ]);
        }
    }
}
