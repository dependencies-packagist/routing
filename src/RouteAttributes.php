<?php

namespace Annotation\Routing;

use Annotation\Route\Contracts\ResourceContract;
use Annotation\Route\Contracts\RoutingContract;
use Annotation\Route\Domain;
use Annotation\Route\Group;
use Annotation\Route\Middleware;
use Annotation\Route\Prefix;
use Annotation\Route\Resource;
use Annotation\Route\Routing\Config;
use Annotation\Route\Routing\Defaults;
use Annotation\Route\ScopeBindings;
use Annotation\Route\Singleton;
use Annotation\Route\Where;
use Annotation\Route\WithoutMiddleware;
use Annotation\Route\WithTrashed;
use Illuminate\Support\Str;
use ReflectionAttribute;
use Reflective\Reflection\ReflectionClass;

class RouteAttributes
{
    public function __construct(
        private readonly ReflectionClass $class,
    )
    {
        //
    }

    public function prefix(): ?string
    {
        $prefix = $this->getAttribute(Prefix::class, function (Prefix $attribute, mixed $prefix = []) {
            foreach (explode('/', $attribute->prefix) as $item) {
                $prefix[] = $item;
            }
            return array_filter($prefix);
        }, []);

        return implode('/', $prefix) ?: null;
    }

    /**
     * @return string
     * @deprecated unUse
     */
    public function getPrefixByControllerName(): string
    {
        return Str::of($this->class->getNamespaceName())
            ->explode('\\')
            ->slice(3)
            ->push(Str::replaceLast('Controller', '', $this->class->getShortName()))
            ->map(static fn($segment) => Str::kebab($segment))
            ->implode('/');
    }

    public function getPrefixWithDomain(?string $prefix = null, mixed $domain = false, ?string $default = null): ?string
    {
        $prefix = $prefix ?: $default ?: null;

        if (is_null($prefix)) {
            return null;
        }

        $prefix = explode('/', $prefix);

        return implode('/', $domain ? array_slice($prefix, 1) : $prefix) ?: null;
    }

    protected function getNameFormPrefix(?string $prefix = null, ?string $default = null): ?string
    {
        $prefix = $prefix ?: $default ?: null;

        if (is_null($prefix)) {
            return null;
        }

        return str_replace('/', '.', trim($prefix, '/') . '/');
    }

    public function domain(): ?string
    {
        return $this->getAttribute(Domain::class, static fn(Domain $attribute) => $attribute->domain, $this->config());
    }

    public function config(): ?string
    {
        return $this->getAttribute(Config::class, static fn(Config $attribute) => config($attribute->key, $attribute->value));
    }

    public function groups(): array
    {
        $group = $this->getAttribute(Group::class, function (Group $attribute, mixed $groups = []) {
            $prefix   = $groups['prefix'] ?? [];
            $domain   = $groups['domain'] ?? [];
            $where    = $groups['where'] ?? [];
            $as       = $groups['as'] ?? [];
            $domain[] = $attribute->domain;
            $as[]     = trim($this->getNameFormPrefix($attribute->as, $attribute->prefix), '.');
            foreach (explode('/', $attribute->prefix) as $item) {
                $prefix[] = $item;
            }
            return [
                'prefix' => array_filter($prefix),
                'domain' => array_filter($domain),
                'where'  => array_merge($where, $attribute->where),
                'as'     => array_filter($as),
            ];
        }, []);

        // if (count($group) == 0) {
        //     if ($this->prefix()) {
        //         $prefix = explode('/', $this->prefix()) + explode('/', $this->getPrefixByControllerName());
        //         $prefix = implode('/', $prefix);
        //     } else {
        //         $prefix = $this->getPrefixByControllerName();
        //     }
        //     $prefix = str_replace($this->prefix(), '', $prefix);
        //     return array_filter([
        //         'prefix' => $this->getPrefixWithDomain($prefix, $this->domain()),
        //         'as'     => $this->getNameFormPrefix($prefix),
        //     ]);
        // }

        $prefix = $group['prefix'] ?? [];
        $domain = $group['domain'] ?? [];
        $where  = $group['where'] ?? [];
        $as     = $group['as'] ?? [];

        $prefix = implode('/', $prefix);
        $as     = implode('/', $as);

        return array_filter([
            'prefix' => $this->getPrefixWithDomain($prefix),
            'domain' => end($domain),
            'where'  => $where,
            'as'     => $this->getNameFormPrefix($as),
        ]);
    }

    public function getDefaultGroupAttribute(): array
    {
        return array_filter([
            'prefix' => $this->getPrefixWithDomain($this->prefix(), $this->domain()),
            'domain' => $this->domain(),
            'as'     => $this->getNameFormPrefix($this->prefix()),
        ]);
    }

    public function isResourceContract(): bool
    {
        return $this->getAttribute(
            ResourceContract::class,
            static fn(ResourceContract $attribute) => property_exists($attribute, 'resource') || property_exists($attribute, 'singleton')
        ) ?? false;
    }

    public function isResource(): bool
    {
        return $this->getAttribute(
            Resource::class,
            static fn(Resource $attribute) => property_exists($attribute, 'resource')
        ) ?? false;
    }

    public function isSingleton(): bool
    {
        return $this->getAttribute(
            Singleton::class,
            static fn(Singleton $attribute) => property_exists($attribute, 'singleton')
        ) ?? false;
    }

    public function resource(): ?string
    {
        return $this->getAttribute(Resource::class, static fn(Resource $attribute) => $attribute->resource);
    }

    public function shallow(): bool|null
    {
        return $this->getAttribute(Resource::class, static fn(Resource $attribute) => $attribute->shallow);
    }

    public function apiResource(): ?string
    {
        return $this->getAttribute(Resource::class, static fn(Resource $attribute) => $attribute->apiResource);
    }

    public function singleton(): ?string
    {
        return $this->getAttribute(Singleton::class, static fn(Singleton $attribute) => $attribute->singleton);
    }

    public function creatable(): bool|null
    {
        return $this->getAttribute(Singleton::class, static fn(Singleton $attribute) => $attribute->creatable);
    }

    public function destroyable(): bool|null
    {
        return $this->getAttribute(Singleton::class, static fn(Singleton $attribute) => $attribute->destroyable);
    }

    public function apiSingleton(): ?string
    {
        return $this->getAttribute(Singleton::class, static fn(Singleton $attribute) => $attribute->apiSingleton);
    }

    public function parameters(): array|string|null
    {
        return $this->getAttribute(
            [Resource::class, Singleton::class],
            static fn(Resource|Singleton $attribute) => $attribute->parameters
        );
    }

    public function except(): string|array|null
    {
        return $this->getAttribute(
            [Resource::class, Singleton::class],
            static fn(Resource|Singleton $attribute) => $attribute->except
        );
    }

    public function only(): string|array|null
    {
        return $this->getAttribute(
            [Resource::class, Singleton::class],
            static fn(Resource|Singleton $attribute) => $attribute->only
        );
    }

    public function names(): string|array|null
    {
        return $this->getAttribute(
            [Resource::class, Singleton::class],
            function (Resource $attribute) {
                if (is_array($attribute->names)) {
                    return $attribute->names;
                }
                return trim($this->getNameFormPrefix($attribute->names, $attribute->resource), '.');
            }
        );
    }

    public function middleware(): array
    {
        return $this->getAttribute(
            Middleware::class,
            static fn(Middleware $attribute, mixed $middleware = []) => array_merge($middleware, $attribute->middleware)
        ) ?? [];
    }

    public function withoutMiddleware(): array
    {
        return $this->getAttribute(
            WithoutMiddleware::class,
            static fn(WithoutMiddleware $attribute, mixed $middleware = []) => array_merge($middleware, $attribute->withoutMiddleware)
        ) ?? [];
    }

    public function wheres(): array
    {
        return $this->getAttribute(
            Where::class,
            static fn(Where $attribute, mixed $wheres = []) => array_merge($wheres, [
                $attribute->param => $attribute->constraint,
            ])
        ) ?? [];
    }

    public function defaults(): array
    {
        return $this->getAttribute(
            Defaults::class,
            static fn(Defaults $attribute, mixed $defaults = []) => array_merge($defaults, [
                $attribute->key => $attribute->value,
            ])
        ) ?? [];
    }

    public function scopeBindings(): ?bool
    {
        return $this->getAttribute(
            ScopeBindings::class,
            static fn(ScopeBindings $attribute) => $attribute->scopeBindings,
            config('routing.scope_bindings')
        );
    }

    public function withTrashed(): bool
    {
        return $this->getAttribute(
            WithTrashed::class,
            static fn(WithTrashed $attribute) => $attribute->withTrashed
        ) ?? false;
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
    protected function getAttribute(array|string $attributes, callable $callable = null, mixed $default = null, int $flags = ReflectionAttribute::IS_INSTANCEOF)
    {
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $attributes = array_reduce($attributes, function (array $initial, string $attribute) use ($flags) {
            $attributes = [];
            foreach ($this->class->getDeclaredParentClass() as $name => $parent) {
                // $attributes[$name] = $parent->getAttributes($attribute, $flags);
                $attributes = array_merge($parent->getAttributes($attribute, $flags), $attributes);
            }
            return array_merge($initial, $attributes);
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
}
