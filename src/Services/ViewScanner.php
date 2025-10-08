<?php

namespace ZalaNihir\DeadcodeScanner\Services;

class ViewScanner
{
    /** @var array<string> */
    protected array $ignoredViews;

    public function __construct()
    {
        $ignore = (array) (config('deadcode.ignore') ?? []);
        $this->ignoredViews = array_values(array_filter((array)($ignore['views'] ?? [])));
    }
    /**
     * Return all blade view names found under resources/views.
     * Example: resources/views/emails/new_user.blade.php => "emails.new_user"
     *
     * @param string|null $viewsPath
     * @return array
     */
    public function getAllViews(?string $viewsPath = null): array
    {
        $viewsPath = $viewsPath ?: resource_path('views');
        if (! is_dir($viewsPath)) {
            return [];
        }

        $files = $this->getBladeFiles($viewsPath);
        $views = [];

        foreach ($files as $file) {
            $relative = str_replace($viewsPath . DIRECTORY_SEPARATOR, '', $file);
            $viewName = preg_replace('/\.blade\.php$/', '', $relative);
            $viewName = str_replace(DIRECTORY_SEPARATOR, '.', $viewName);
            if (! in_array($viewName, $this->ignoredViews, true)) {
                $views[] = $viewName;
            }
        }

        return array_values(array_unique($views));
    }

    /**
     * Search the project for literal view references (simple heuristics).
     * It will find patterns like `view('x.y')`, `View::make('x.y')`, and blade directives
     * like `@include('x.y')`, `@extends('x.y')`, `@component('x.y')`, `@each('x.y', ...)`.
     *
     * @param array|null $paths
     * @return array
     */
    public function findReferencedViews(?array $paths = null): array
    {
        $paths = $paths ?: [app_path(), resource_path('views'), base_path('routes')];

        $patterns = [
            // php-style view calls
            "/view\\(\\s*['\"]([^'\"]+)['\"]/i",
            "/View::make\\(\\s*['\"]([^'\"]+)['\"]/i",
            "/return\\s+view\\(\\s*['\"]([^'\"]+)['\"]/i",
            "/response\\(\\)->view\\(\\s*['\"]([^'\"]+)['\"]/i",

            // blade directives @include('x'), @extends('x'), @component('x'), @each('x', ...)
            "/@(?:include|extends|component|each|includeFirst|includeIf|includeWhen|includeUnless)\\(\\s*['\"]([^'\"]+)['\"]/i",
        ];

        $found = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($it as $f) {
                if (! $f->isFile()) {
                    continue;
                }

                // only scan PHP and blade files (blade files end with .blade.php but extension is php)
                $filename = $f->getFilename();
                if (! str_ends_with($filename, '.php') && ! str_ends_with($filename, '.blade.php')) {
                    continue;
                }

                $content = @file_get_contents($f->getPathname());
                if (! $content) {
                    continue;
                }

                foreach ($patterns as $pat) {
                    if (preg_match_all($pat, $content, $matches)) {
                        foreach ($matches[1] as $m) {
                            // normalize slashes to dot-notation
                            $m = preg_replace('#/+#', '.', trim($m));
                            if (! in_array($m, $this->ignoredViews, true)) {
                                $found[] = $m;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Return views that exist but are not referenced by the heuristics above.
     *
     * @return array
     */
    public function findUnusedViews(): array
    {
        $all  = $this->getAllViews();
        $refs = $this->findReferencedViews();

        // quick diff
        $unused = array_values(array_diff($all, $refs));

        return $unused;
    }

    /**
     * Recursively collect blade file paths.
     *
     * @param string $path
     * @return array
     */
    protected function getBladeFiles(string $path): array
    {
        $files = [];
        if (! is_dir($path)) {
            return $files;
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }
            if (str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }
}
