<?php

namespace Annotation\Routing;

use Annotation\Route\Contracts\RouteContract;
use Annotation\Route\Contracts\RoutingContract;
use Annotation\Route\Contracts\WhereContract;
use Annotation\Route\Middleware;
use Annotation\Route\Routing\Defaults;
use Annotation\Route\ScopeBindings;
use Annotation\Route\WithoutMiddleware;
use Annotation\Route\WithTrashed;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class RouteMethodAttributes
{
    public function __construct(
        protected readonly ReflectionClass  $class,
        protected readonly RouteAttributes  $routeAttributes,
        protected readonly ReflectionMethod $method,
    )
    {
        //
    }

    public function getRouteAction(): array|string
    {
        return $this->method->getName() == '__invoke' ? $this->class->getName() : [$this->class->getName(), $this->method->getName()];
    }

    public function getRouteName(): string
    {
        return Str::kebab($this->method->getName());
    }

    /**
     * @param Route $route
     *
     * @return static
     */
    public function setScopeBindingsIfAvailable(Route $route): static
    {
        $scopeBindings = $this->getAttribute(
            ScopeBindings::class,
            static fn(ScopeBindings $scopeBindings) => $scopeBindings->scopeBindings
        ) ?? $this->routeAttributes->scopeBindings();

        match ($scopeBindings) {
            true => $route->scopeBindings(),
            false => $route->withoutScopedBindings(),
            default => null,
        };

        return $this;
    }

    /**
     * @param Route $route
     *
     * @return static
     */
    public function setWheresIfAvailable(Route $route): static
    {
        $route->setWheres(array_merge($this->routeAttributes->wheres(), $this->getAttribute(
            WhereContract::class,
            static fn(WhereContract $whereContract, array $wheres = []) => array_merge($wheres, $whereContract->toArray())
        ) ?? []));

        return $this;
    }

    /**
     * @param Route $route
     *
     * @return static
     */
    public function setDefaultsIfAvailable(Route $route): static
    {
        $route->setDefaults(array_merge($this->routeAttributes->defaults(), $this->getAttribute(
            Defaults::class,
            static fn(Defaults $defaults, array $initial = []) => array_merge($initial, $defaults->toArray())
        ) ?? []));

        return $this;
    }

    /**
     * @param Route $route
     * @param array $middleware
     *
     * @return static
     */
    public function addMiddlewareToRoute(Route $route, array $middleware): static
    {
        $route->middleware([
            ...$this->routeAttributes->middleware(),
            ...$this->getAttribute(
                Middleware::class,
                static fn(Middleware $middleware, array $defaults = []) => array_merge($defaults, $middleware->middleware)
            ) ?? [],
            ...$middleware,
        ]);

        return $this;
    }

    /**
     * @param Route $route
     * @param array $middleware
     *
     * @return static
     */
    public function addWithoutMiddlewareToRoute(Route $route, array $middleware): static
    {
        $route->withoutMiddleware([
            ...$this->routeAttributes->withoutMiddleware(),
            ...$this->getAttribute(
                WithoutMiddleware::class,
                static fn(WithoutMiddleware $middleware, array $defaults = []) => array_merge($defaults, $middleware->withoutMiddleware)
            ) ?? [],
            ...$middleware,
        ]);

        return $this;
    }

    /**
     * @param Route $route
     *
     * @return static
     */
    public function setWithTrashedIfAvailable(Route $route): static
    {
        $withTrashed = $this->getAttribute(
            WithTrashed::class,
            static fn(WithTrashed $withTrashed) => $withTrashed->withTrashed
        ) ?? $this->routeAttributes->withTrashed();

        if (!is_null($withTrashed)) {
            $route->withTrashed($withTrashed);
        }

        return $this;
    }

    /**
     * @template T of RoutingContract
     * @param class-string<T>|array<class-string<T>> $attributes
     * @param callable|null                          $callable
     * @param mixed|null                             $default
     * @param int                                    $flags
     *
     * @return T|mixed
     */
    public function getAttribute(array|string $attributes, callable $callable = null, mixed $default = null, int $flags = ReflectionAttribute::IS_INSTANCEOF)
    {
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $attributes = array_reduce($attributes, function (array $initial, string $attribute) use ($flags) {
            return array_merge($initial, $this->method->getAttributes($attribute, $flags));
        }, []);

        if (count($attributes) === 0) {
            return $default;
        }

        return array_reduce($attributes, function (mixed $initial, ReflectionAttribute $attribute) use ($callable) {
            $instance = $attribute->newInstance();
            if (is_callable($callable)) {
                return call_user_func($callable, $instance, $initial);
            }
            return $instance;
        }, []);
    }

    /**
     * @return \Annotation\Route\Route[]
     */
    public function getMethodAttributes(): array
    {
        return $this->getAttribute(
            RouteContract::class,
            static fn(RouteContract $routeContract, array $routeAttributes) => array_merge($routeAttributes, [$routeContract])
        ) ?? [];
    }
}
