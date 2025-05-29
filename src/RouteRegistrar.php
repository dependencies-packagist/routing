<?php

namespace Annotation\Routing;

use Annotation\Route\Contracts\RoutingContract;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\PendingSingletonResourceRegistration;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;
use Reflective\Reflection\ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class RouteRegistrar
{
    protected string $basePath;

    protected string $rootNamespace;

    protected array $middleware        = [];
    protected array $withoutMiddleware = [];

    public function __construct(protected Router $router)
    {
        $this->useBasePath(app()->path());
    }

    /**
     * @param string $basePath
     *
     * @return $this
     */
    public function useBasePath(string $basePath): self
    {
        $this->basePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);

        return $this;
    }

    /**
     * @param string $rootNamespace
     *
     * @return $this
     */
    public function useRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = rtrim(str_replace('/', '\\', $rootNamespace), '\\') . '\\';

        return $this;
    }

    /**
     * @param string|array $middleware
     *
     * @return $this
     */
    public function useMiddleware(string|array $middleware): self
    {
        $this->middleware = Arr::wrap($middleware);

        return $this;
    }

    public function middleware(): array
    {
        return $this->middleware ?? [];
    }

    /**
     * @param string|array $withoutMiddleware
     *
     * @return $this
     */
    public function useWithoutMiddleware(string|array $withoutMiddleware): self
    {
        $this->withoutMiddleware = Arr::wrap($withoutMiddleware);

        return $this;
    }

    public function withoutMiddleware(): array
    {
        return $this->withoutMiddleware ?? [];
    }

    /**
     * @param string|array $directories
     * @param array        $patterns
     * @param array        $notPatterns
     *
     * @return void
     */
    public function registerDirectory(string|array $directories, array $patterns = [], array $notPatterns = []): void
    {
        $directories = Arr::wrap($directories);
        $patterns    = $patterns ?: ['*.php'];

        $files = (new Finder())->files()->in($directories)->name($patterns)->notName($notPatterns)->sortByName();

        collect($files)->each(fn(SplFileInfo $file) => $this->registerFile($file));
    }

    /**
     * @param string|SplFileInfo $path
     *
     * @return void
     */
    public function registerFile(string|SplFileInfo $path): void
    {
        if (is_string($path)) {
            $path = new SplFileInfo($path);
        }

        $fullyQualifiedClassName = $this->fullQualifiedClassNameFromFile($path);

        $this->processAttributes($fullyQualifiedClassName);
    }

    /**
     * @param SplFileInfo $file
     *
     * @return string
     */
    protected function fullQualifiedClassNameFromFile(SplFileInfo $file): string
    {
        $class = trim(Str::replaceFirst($this->basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        $class = str_replace(
            [DIRECTORY_SEPARATOR, 'App\\'],
            ['\\', app()->getNamespace()],
            Str::replaceLast('.php', '', $class)
        );

        return $this->rootNamespace . $class;
    }

    /**
     * @param string $class
     *
     * @return void
     */
    public function registerClass(string $class): void
    {
        $this->processAttributes($class);
    }

    /**
     * @param string $className
     *
     * @return void
     */
    protected function processAttributes(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        try {
            $routeAttributes = new RouteAttributes($class = new ReflectionClass($className));

            $this->registerRoutes($class, $routeAttributes);
        } catch (ReflectionException $e) {
        }
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return void
     */
    protected function registerRoutes(ReflectionClass $class, RouteAttributes $routeAttributes): void
    {
        if ($routeAttributes->isResourceContract()) {
            $this->group(array_filter([
                'domain' => $routeAttributes->config() ?? $routeAttributes->domain(),
                'prefix' => $routeAttributes->prefix(),
            ]), fn() => $this->getResourceRoutes($class, $routeAttributes));
            return;
        }

        foreach ($routeAttributes->groups() as $group) {
            $this->group($group, fn() => $this->getRoutes($class, $routeAttributes));
        }
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return RouteMethodAttributes[]
     */
    protected function getDeclaringMethods(ReflectionClass $class, RouteAttributes $routeAttributes): array
    {
        $methods = array_filter($class->getDeclaredMethods(ReflectionMethod::IS_PUBLIC), function (ReflectionMethod $method) use ($class) {
            return count($method->getAttributes(RoutingContract::class, ReflectionAttribute::IS_INSTANCEOF));
        });
        return array_map(function (ReflectionMethod $method) use ($class, $routeAttributes) {
            return new RouteMethodAttributes($class, $routeAttributes, $method);
        }, $methods);
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return void
     */
    public function getRoutes(ReflectionClass $class, RouteAttributes $routeAttributes): void
    {
        foreach ($methods = $this->getDeclaringMethods($class, $routeAttributes) as $method) {
            foreach ($attributes = $method->getMethodAttributes() as $attribute) {
                $route = $this->router
                    ->addRoute($attribute->getMethods(), $attribute->getUri($method->getRouteName()), $method->getRouteAction())
                    ->name($attribute->getName($method->getRouteName()))
                    ->middleware($this->middleware())
                    ->withoutMiddleware($this->withoutMiddleware());
                $method->setScopeBindingsIfAvailable($route)
                    ->setWithTrashedIfAvailable($route)
                    ->setWheresIfAvailable($route)
                    ->setDefaultsIfAvailable($route)
                    ->addMiddlewareToRoute($route, $attribute->getMiddleware())
                    ->addWithoutMiddlewareToRoute($route, $attribute->getWithoutMiddleware());
            }
        }
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return PendingResourceRegistration
     */
    protected function getRouteForResource(ReflectionClass $class, RouteAttributes $routeAttributes): PendingResourceRegistration
    {
        return $routeAttributes->apiResource()
            ? $this->router->apiResource($routeAttributes->resource(), $class->getName())
            : $this->router->resource($routeAttributes->resource(), $class->getName());
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return PendingSingletonResourceRegistration
     */
    protected function getRouteForSingletonResource(ReflectionClass $class, RouteAttributes $routeAttributes): PendingSingletonResourceRegistration
    {
        return $routeAttributes->apiSingleton()
            ? $this->router->apiSingleton($routeAttributes->singleton(), $class->getName())
            : $this->router->singleton($routeAttributes->singleton(), $class->getName());
    }

    /**
     * @param ReflectionClass $class
     * @param RouteAttributes $routeAttributes
     *
     * @return void
     */
    public function getResourceRoutes(ReflectionClass $class, RouteAttributes $routeAttributes): void
    {
        $route = match (true) {
            $routeAttributes->isSingleton() => $this->getRouteForSingletonResource($class, $routeAttributes),
            default => $this->getRouteForResource($class, $routeAttributes)
        };

        $methods = [
            'only',
            'except',
            'names',
            'parameters',
            'shallow',
        ];

        foreach ($methods as $method) {
            if (!method_exists($route, $method)) {
                continue;
            }
            if (!is_null($value = call_user_func([$routeAttributes, $method]))) {
                call_user_func([$route, $method], $value);
            }
        }

        $methods = [
            'creatable',
            'destroyable',
        ];

        foreach ($methods as $method) {
            if (!method_exists($route, $method)) {
                continue;
            }
            if (call_user_func([$routeAttributes, $method]) === true) {
                call_user_func([$route, $method]);
            }
        }

        $route->middleware([
            ...$this->middleware(),
            ...$routeAttributes->middleware(),
        ])->withoutMiddleware([
            ...$this->withoutMiddleware(),
            ...$routeAttributes->withoutMiddleware(),
        ]);
    }

    /**
     * @param array $directories
     *
     * @return void
     */
    public function directories(array $directories): void
    {
        foreach ($directories as $directory => $attributes) {
            if (is_string($attributes)) {
                [$directory, $attributes] = [$attributes, []];
            }
            $options       = Arr::except($attributes, ['namespace', 'base_path', 'patterns', 'not_patterns']);
            $rootNamespace = $attributes['namespace'] ?? app()->getNamespace();
            $basePath      = $attributes['base_path'] ?? (isset($attributes['namespace']) ? $directory : app()->path());
            $patterns      = $attributes['patterns'] ?? [];
            $notPatterns   = $attributes['not_patterns'] ?? [];
            $this->useRootNamespace($rootNamespace)
                ->useBasePath($basePath)
                ->group($options, fn() => $this->registerDirectory($directory, $patterns, $notPatterns));
        }
    }

    /**
     * @param array    $options
     * @param callable $routes
     *
     * @return $this
     */
    public function group(array $options, callable $routes): self
    {
        $this->router->group($options, $routes);

        return $this;
    }
}
