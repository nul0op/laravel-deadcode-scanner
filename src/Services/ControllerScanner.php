<?php

namespace ZalaNihir\DeadcodeScanner\Services;

use ReflectionClass;
use ReflectionMethod;

class ControllerScanner
{
    /** @var array controllerClass => [used methods] */
    protected array $usedMethods = [];

    public function __construct(array $usedMethods = [])
    {
        $this->usedMethods = $usedMethods;
    }

    /**
     * Scan controllers (recursively under app/Http/Controllers) and return
     * array of class => [unused public method names].
     *
     * @param string|null $controllersPath
     * @return array
     */
    public function scanControllers(?string $controllersPath = null): array
    {
        $controllersPath = $controllersPath ?: app_path('Http/Controllers');

        if (! is_dir($controllersPath)) {
            return [];
        }

        $files = $this->getPhpFiles($controllersPath);
        $unused = [];

        foreach ($files as $file) {
            $fqcn = $this->getClassFullNameFromFile($file);
            if (! $fqcn) {
                continue;
            }

            // try to ensure class is loaded
            if (! class_exists($fqcn)) {
                try {
                    require_once $file;
                } catch (\Throwable $e) {
                    // ignore load errors
                }
            }

            if (! class_exists($fqcn)) {
                continue;
            }

            try {
                $ref = new ReflectionClass($fqcn);
            } catch (\ReflectionException $e) {
                continue;
            }

            $methods = [];
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // only methods declared in this class (skip inherited)
                if ($method->getDeclaringClass()->getName() !== $fqcn) {
                    continue;
                }

                // skip constructor, magic, static, abstract
                $mName = $method->getName();
                if ($method->isConstructor() || $method->isStatic() || $method->isAbstract()) {
                    continue;
                }
                if (str_starts_with($mName, '__')) {
                    continue;
                }

                $usedForClass = $this->usedMethods[$fqcn] ?? [];

                if (! in_array($mName, $usedForClass, true)) {
                    $methods[] = $mName;
                }
            }

            if (! empty($methods)) {
                $unused[$fqcn] = $methods;
            }
        }

        return $unused;
    }

    /**
     * Recursively collect PHP files under a directory.
     *
     * @param string $dir
     * @return array
     */
    protected function getPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $f) {
            if (! $f->isFile()) {
                continue;
            }
            if ($f->getExtension() !== 'php') {
                continue;
            }
            $files[] = $f->getPathname();
        }
        return $files;
    }

    /**
     * Try to extract the fully-qualified class name from a PHP file by reading namespace + class token.
     *
     * @param string $path
     * @return string|null
     */
    protected function getClassFullNameFromFile(string $path): ?string
    {
        $content = @file_get_contents($path);
        if (! $content) {
            return null;
        }

        // namespace
        $namespace = null;
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $m)) {
            $namespace = trim($m[1]);
        }

        // class, trait or interface
        if (preg_match('/^(?:abstract\s+|final\s+)?class\s+([A-Za-z0-9_]+)/m', $content, $m2)) {
            $class = $m2[1];
        } elseif (preg_match('/^trait\s+([A-Za-z0-9_]+)/m', $content, $m2)) {
            $class = $m2[1];
        } else {
            return null;
        }

        return $namespace ? ($namespace . '\\' . $class) : $class;
    }
}
