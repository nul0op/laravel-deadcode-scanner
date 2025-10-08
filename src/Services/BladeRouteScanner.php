<?php

namespace ZalaNihir\DeadcodeScanner\Services;

use Illuminate\Support\Facades\Route;

class BladeRouteScanner
{
    /** @var array<string> */
    protected array $ignoredRoutes;

    public function __construct()
    {
        $ignore = (array) (config('deadcode.ignore') ?? []);
        $this->ignoredRoutes = array_values(array_filter((array)($ignore['routes'] ?? [])));
    }
    /**
     * Scan blade files for routes that don't exist
     *
     * @param string|null $viewsPath
     * @return array
     */
    public function scanMissingRoutes(?string $viewsPath = null): array
    {
        $viewsPath = $viewsPath ?: resource_path('views');
        $missing = [];

        // Get all defined route names in the application
        $routes = collect(Route::getRoutes())
            ->reject(function ($r) {
                $name = method_exists($r, 'getName') ? $r->getName() : null;
                $uri  = method_exists($r, 'uri') ? $r->uri() : null;
                return in_array($name, $this->ignoredRoutes, true) || in_array($uri, $this->ignoredRoutes, true);
            })
            ->map(fn($r) => $r->getName())
            ->filter()
            ->all();

        // Iterate over all blade files
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsPath));
        foreach ($iterator as $f) {
            if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }

            $lines = file($f->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $i => $line) {
                // Match route('name') calls
                if (preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]\s*\)/", $line, $matches)) {
                    foreach ($matches[1] as $routeName) {
                        if (! in_array($routeName, $routes) && ! in_array($routeName, $this->ignoredRoutes, true)) {
                            $missing[] = [
                                'file' => str_replace(base_path() . '/', '', $f->getPathname()),
                                'line' => $i + 1,
                                'route' => $routeName,
                            ];
                        }
                    }
                }
            }
        }

        return $missing;
    }
}
