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
use ReflectionClass;

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
        return $this->getAttribute(Prefix::class, static fn(Prefix $attribute) => $attribute->prefix);
    }

    public function domain(): ?string
    {
        return $this->getAttribute(Domain::class, static fn(Domain $attribute) => $attribute->domain);
    }

    public function fromConfig(): ?string
    {
        return $this->getAttribute(Config::class, static fn(Config $attribute) => config($attribute->key, $attribute->value));
    }

    public function groups(): array
    {
        return $this->getAttribute(Group::class, function (Group $attribute, mixed $groups = []) {
            $group    = array_filter([
                'domain' => $attribute->domain,
                'prefix' => $attribute->prefix ? trim("{$this->prefix()}/{$attribute->prefix}") : null,
                'where'  => $attribute->where,
                'as'     => $attribute->as,
            ]);
            $groups[] = array_merge($this->getDefaultGroupAttribute(), $group);
            return $groups;
        }, [$this->getDefaultGroupAttribute()]);
    }

    public function getDefaultGroupAttribute(): array
    {
        $as = Str::of($this->class->getNamespaceName())
            ->explode('\\')
            ->slice(3)
            ->push(Str::replaceLast('Controller', '', $this->class->getShortName()))
            ->map(static fn($segment) => Str::kebab($segment));
        return array_filter([
            'domain' => $this->fromConfig() ?? $this->domain(),
            'prefix' => $this->prefix() ?? $as->implode('/'),
            'as'     => $as->push('')->implode('.'),
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
            static fn(Resource|Singleton $attribute) => $attribute->names
        );
    }

    public function middleware(): array
    {
        return $this->getAttribute(
            Middleware::class,
            static fn(Middleware $attribute) => $attribute->middleware
        ) ?? [];
    }

    public function withoutMiddleware(): array
    {
        return $this->getAttribute(
            WithoutMiddleware::class,
            static fn(WithoutMiddleware $attribute) => $attribute->withoutMiddleware
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
            return array_merge($initial, $this->class->getAttributes($attribute, $flags));
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
