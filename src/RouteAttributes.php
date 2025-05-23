<?php

namespace Annotation\Routing;

use Annotation\Route\Contracts\RoutingContract;
use Annotation\Route\Domain;
use Annotation\Route\Group;
use Annotation\Route\Middleware;
use Annotation\Route\Prefix;
use Annotation\Route\Resource;
use Annotation\Route\Routing\Config;
use Annotation\Route\Routing\Defaults;
use Annotation\Route\ScopeBindings;
use Annotation\Route\Where;
use Annotation\Route\WithTrashed;
use ReflectionAttribute;
use ReflectionClass;

class RouteAttributes
{
    public function __construct(
        private ReflectionClass $class,
    )
    {
        //
    }

    public function prefix(): ?string
    {
        if (!$attribute = $this->getAttribute(Prefix::class)) {
            return null;
        }

        return $attribute->prefix;
    }

    public function domain(): ?string
    {
        if (!$attribute = $this->getAttribute(Domain::class)) {
            return null;
        }

        return $attribute->domain;
    }

    public function fromConfig(): ?string
    {
        if (!$attribute = $this->getAttribute(Config::class)) {
            return null;
        }

        return config($attribute->key, $attribute->value);
    }

    public function groups(): array
    {
        $groups = [];

        $attributes = $this->class->getAttributes(Group::class, ReflectionAttribute::IS_INSTANCEOF);

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                $attributeClass = $attribute->newInstance();
                $groups[]       = array_filter([
                    'domain' => $attributeClass->domain,
                    'prefix' => $attributeClass->prefix,
                    'where'  => $attributeClass->where,
                    'as'     => $attributeClass->as,
                ]);
            }
        } else {
            $groups[] = array_filter([
                'domain' => $this->fromConfig() ?? $this->domain(),
                'prefix' => $this->prefix(),
            ]);
        }

        return $groups;
    }

    public function resource(): ?string
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->resource;
    }

    public function parameters(): array|string|null
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->parameters;
    }

    public function shallow(): bool|null
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->shallow;
    }

    public function apiResource(): ?string
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->apiResource;
    }

    public function except(): string|array|null
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->except;
    }

    public function only(): string|array|null
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->only;
    }

    public function names(): string|array|null
    {
        if (!$attribute = $this->getAttribute(Resource::class)) {
            return null;
        }

        return $attribute->names;
    }

    public function middleware(): array
    {
        if (!$attribute = $this->getAttribute(Middleware::class)) {
            return [];
        }

        return $attribute->middleware;
    }

    public function scopeBindings(): ?bool
    {
        if (!$attribute = $this->getAttribute(ScopeBindings::class)) {
            return config('routing.scope-bindings');
        }

        return $attribute->scopeBindings;
    }

    public function wheres(): array
    {
        $wheres     = [];
        $attributes = $this->class->getAttributes(Where::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $attributeClass                 = $attribute->newInstance();
            $wheres[$attributeClass->param] = $attributeClass->constraint;
        }

        return $wheres;
    }

    public function defaults(): array
    {
        $defaults   = [];
        $attributes = $this->class->getAttributes(Defaults::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $attributeClass                 = $attribute->newInstance();
            $defaults[$attributeClass->key] = $attributeClass->value;
        }

        return $defaults;
    }

    public function withTrashed(): bool
    {
        if (!$attribute = $this->getAttribute(WithTrashed::class)) {
            return false;
        }

        return $attribute->withTrashed;
    }

    /**
     * @template T of RoutingContract
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     */
    protected function getAttribute(string $attributeClass)
    {
        $attributes = $this->class->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        if (!count($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
