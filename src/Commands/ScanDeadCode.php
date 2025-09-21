<?php

namespace ZalaNihir\DeadcodeScanner\Commands;

use Illuminate\Console\Command;
use ZalaNihir\DeadcodeScanner\Services\RouteScanner;
use ZalaNihir\DeadcodeScanner\Services\ControllerScanner;
use ZalaNihir\DeadcodeScanner\Services\ViewScanner;
use ZalaNihir\DeadcodeScanner\Services\BladeRouteScanner;

class ScanDeadCode extends Command
{
    protected $signature = 'deadcode:scan {--d|details : show more details}';
    protected $description = 'Scan project for unused routes, controllers, and views';

    public function handle()
    {
        $this->info('🔍 Scanning for dead code...');

        // ------------------------------
        // ROUTES
        // ------------------------------
        $routeScanner = new RouteScanner();
        $routes      = $routeScanner->getRoutes();
        $usedMethods = $routeScanner->getUsedControllerMethods();
        $missing     = $routeScanner->getRoutesPointingToMissing();

        if (! empty($missing)) {
            $this->error("\n🚨 Routes pointing to missing controllers/methods:");
            foreach ($missing as $m) {
                $this->line(" - {$m['uri']} -> {$m['action']} (class: " . ($m['class_exists'] ? 'yes' : 'no') . ", method: " . ($m['method_exists'] ? 'yes' : 'no') . ")");
            }
        } else {
            $this->info("\n✅ No routes pointing to missing controllers/methods.");
        }

        // ------------------------------
        // CONTROLLERS
        // ------------------------------
        $controllerScanner = new ControllerScanner($usedMethods);
        $unusedControllers = $controllerScanner->scanControllers();

        if (! empty($unusedControllers)) {
            $this->warn("\n🚨 Unused controller methods:");
            foreach ($unusedControllers as $class => $methods) {
                foreach ($methods as $m) {
                    $this->line(" - {$class}::{$m}()");
                }
            }
        } else {
            $this->info("\n✅ No unused controller methods detected.");
        }

        // ------------------------------
        // VIEWS
        // ------------------------------
        $viewScanner = new ViewScanner();
        $unusedViews = $viewScanner->findUnusedViews();

        if (! empty($unusedViews)) {
            $this->warn("\n🚨 Unused blade views:");
            foreach ($unusedViews as $view) {
                $this->line(" - {$view}");
            }
        } else {
            $this->info("\n✅ No unused blade views detected.");
        }

        // ------------------------------
        // UNDEFINED ROUTES IN BLADES
        // ------------------------------
        $bladeScanner = new BladeRouteScanner();
        $missingBladeRoutes = $bladeScanner->scanMissingRoutes();

        if (! empty($missingBladeRoutes)) {
            $this->warn("\n🚨 Undefined routes used in Blade templates:");
            foreach ($missingBladeRoutes as $m) {
                $this->line(" - {$m['route']} in {$m['file']} (line {$m['line']})");
            }
        } else {
            $this->info("\n✅ All Blade routes exist.");
        }

        $this->info("\n🔚 Scan finished.");
        return 0;
    }
}
