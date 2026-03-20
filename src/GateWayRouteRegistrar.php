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
        return preg_replace_callback('/\{(\w+)(\?)?\}/', function ($matches) use ($request) {
            return $request->input($matches[1], isset($matches[2]) ? null : $matches[0]);
        }, $route);
    }

    protected function replace(string $search, string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    public function gateway(string $endpoint = 'gateway.do', callable $action = null, callable $version = null): Route
    {
        return Router::any($endpoint, function (Request $request) use ($endpoint, $action, $version) {
            $action  = app()->call($action ?? fn() => $request->input('action'));
            $version = app()->call($version ?? fn() => $request->input('version'));
            $uri     = $this->getRouteByName($action, $version, $default = '{fallbackPlaceholder}');
            $method  = $uri == $default ? 'GET' : $request->getMethod();
            $uri     = $this->replaceRouteParameters($uri, $request);
            $uri     = $this->replace("/{$endpoint}", "/{$uri}", $request->getUri());
            // 将 SymfonyRequest 转换为 Request 实例
            $newRequest = Request::createFromBase(SymfonyRequest::create(
                uri: $uri,
                method: $method,
                cookies: $request->cookies->all(),
                files: $request->files->all(),
                server: $request->server->all(),
                content: $request->getContent()
            ));
            foreach ($request->query->all() as $key => $value) {
                $newRequest->query->set($key, $value);
            }
            foreach ($request->request->all() as $key => $value) {
                $newRequest->request->set($key, $value);
            }
            foreach ($request->attributes->all() as $key => $value) {
                $newRequest->attributes->set($key, $value);
            }
            // 将新的 Request 实例绑定到服务容器
            app()->instance(Request::class, $newRequest);
            //使用路由器来分发子请求
            return Router::dispatch($newRequest);
        })
            ->middleware(config('routing.gateway.middleware', []))
            ->withoutMiddleware(config('routing.gateway.excluded_middleware', []))
            ->name('gateway');
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

        $routes = Router::getRoutes();

        if ($routes->hasNamedRoute($name)) {
            return $routes->getByName($name)->uri();
        }

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
        $collect = collect(explode('.', $name));
        $collect = $collect->map(function ($item) {
            return Str::studly($item);
        });
        $count   = $collect->count();
        $target  = $collect->implode(function ($value, $key) use ($count) {
            if ($key === 0) {
                return app()->getNamespace().'Http\\Controllers\\'.$value.'\\';
            }
            return match ($count - $key) {
                1 => lcfirst($value),
                2 => $value.'Controller'.'@',
                default => $value.'\\'
            };
        });

        [$class, $method] = Str::parseCallback($target, '__invoke');

        try {
            $reflection = new ReflectionMethod($class, $method);
            if ($reflection->isPublic() && $reflection->getDeclaringClass()->getName() === $class) {
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
