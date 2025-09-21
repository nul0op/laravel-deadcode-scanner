<?php

namespace ZalaNihir\DeadcodeScanner\Services;

use Illuminate\Support\Facades\Route;

class RouteScanner
{
    /**
     * Return a list of all routes (simple array).
     *
     * @return array
     */
    public function getRoutes(): array
    {
        $out = [];

        foreach (Route::getRoutes() as $route) {
            $out[] = [
                'uri'        => $route->uri(),
                'methods'    => $route->methods(),
                'name'       => $route->getName(),
                'action'     => $route->getActionName(),
                'middleware' => method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : ($route->middleware() ?? []),
            ];
        }

        return $out;
    }

    /**
     * Return an associative array of controller class => [methods used]
     *
     * @return array
     */
    public function getUsedControllerMethods(): array
    {
        $used = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! $action) {
                continue;
            }

            // skip closures
            if (strpos($action, 'Closure') !== false) {
                continue;
            }

            // forms: Class@method, Class::method, or Class (invokable)
            if (strpos($action, '@') !== false) {
                [$class, $method] = explode('@', $action, 2);
            } elseif (strpos($action, '::') !== false) {
                [$class, $method] = explode('::', $action, 2);
            } else {
                $class  = $action;
                $method = '__invoke';
            }

            if (! isset($used[$class])) {
                $used[$class] = [];
            }

            $used[$class][] = $method;
        }

        // unique methods per class
        foreach ($used as $k => $v) {
            $used[$k] = array_values(array_unique($v));
        }

        return $used;
    }

    /**
     * Find routes that point to missing classes or missing methods.
     *
     * @return array
     */
    public function getRoutesPointingToMissing(): array
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! $action || strpos($action, 'Closure') !== false) {
                continue;
            }

            if (strpos($action, '@') !== false) {
                [$class, $method] = explode('@', $action, 2);
            } elseif (strpos($action, '::') !== false) {
                [$class, $method] = explode('::', $action, 2);
            } else {
                $class  = $action;
                $method = '__invoke';
            }

            $classExists  = class_exists($class);
            $methodExists = $classExists ? method_exists($class, $method) : false;

            if (! $classExists || ! $methodExists) {
                $missing[] = [
                    'uri'          => $route->uri(),
                    'action'       => $action,
                    'class_exists' => $classExists,
                    'method_exists' => $methodExists,
                ];
            }
        }

        return $missing;
    }
}
