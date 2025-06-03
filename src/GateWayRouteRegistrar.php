<?php

namespace Annotation\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class GateWayRouteRegistrar
{
    protected function replaceRouteParameters(string $route, Request $request): string
    {
        return preg_replace_callback('/{(\w+)}/', function ($matches) use ($request) {
            return $request->input($matches[1], $matches[0]);
        }, $route);
    }

    public function gateway(string $endpoint = 'gateway.do', callable $action = null, callable $version = null): Route
    {
        return Router::any($endpoint, function (Request $request) use ($action, $version) {
            $action  = app()->call($action ?? fn() => $request->input('action'));
            $version = app()->call($version ?? fn() => $request->input('version'));
            $uri     = $this->getRouteByName($action, $version, $default = '{fallbackPlaceholder}');
            $method  = $uri == $default ? 'GET' : $request->getMethod();
            $uri     = $this->replaceRouteParameters($uri, $request);
            // 将 SymfonyRequest 转换为 Request 实例
            $request = Request::createFromBase(SymfonyRequest::create(
                $uri,
                $method,
                $request->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            ));
            // 将新的 Request 实例绑定到服务容器
            app()->instance(Request::class, $request);
            //使用路由器来分发子请求
            return Router::dispatch($request);
        })->middleware(config('routing.gateway_middleware', []))->name('gateway');
    }

    protected function getRouteByName(string $action = null, string $version = null, string $default = '{fallbackPlaceholder}'): string
    {
        if (is_null($action)) {
            return $default;
        }

        $alias = collect(config('routing.alias', []))->get($version ?: '1.0.0', []);
        $name  = collect($alias)->reduce(function ($target, $value, $key) {
            return stripos($target, $key) === 0 ? str_ireplace($key, $value, $target) : $target;
        }, $action);

        return $this->resolveRoute($name, $default);
    }

    /**
     * Get the URI associated with the route.
     *
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    protected function resolveRoute(string $name, string $default = '{fallbackPlaceholder}'): string
    {
        $routes = Router::getRoutes();

        if ($routes->hasNamedRoute($name)) {
            return $routes->getByName($name)->uri();
        }

        $collect = collect(explode('.', $name));
        $collect = $collect->map(function ($item) {
            return Str::studly($item);
        });
        $count   = $collect->count();
        $target  = $collect->implode(function ($value, $key) use ($count) {
            if ($key === 0) {
                return app()->getNamespace() . 'Http\\Controllers\\' . $value . '\\';
            }
            return match ($count - $key) {
                1 => lcfirst($value),
                2 => $value . 'Controller' . '@',
                default => $value . '\\'
            };
        });

        [$class, $method] = Str::parseCallback($target, '__invoke');

        try {
            $reflection = new ReflectionMethod($class, $method);
            if ($reflection->isPublic()) {
                return Router::post($collect->map(function ($item) {
                    return Str::kebab($item);
                })->implode('/'), $target)->name($name)->uri();
            }
        } catch (ReflectionException $e) {
            //
        }

        return $default;
    }
}
