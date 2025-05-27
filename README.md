<a id="readme-top"></a>

# Laravel Annotation Routing

Use PHP 8 attributes to register routes in a Laravel Application.

[![GitHub Tag][GitHub Tag]][GitHub Tag URL]
[![Total Downloads][Total Downloads]][Packagist URL]
[![Packagist Version][Packagist Version]][Packagist URL]
[![Packagist PHP Version Support][Packagist PHP Version Support]][Repository URL]
[![Packagist License][Packagist License]][Repository URL]

<!-- TABLE OF CONTENTS -->
<details>
    <summary>Table of Contents</summary>
    <ol>
        <li><a href="#quick-start">Quick Start</a></li>
        <li><a href="#installation">Installation</a></li>
        <li><a href="#usage">Usage</a></li>
        <li><a href="#contributing">Contributing</a></li>
        <li><a href="#contributors">Contributors</a></li>
        <li><a href="#license">License</a></li>
    </ol>
</details>

## Quick Start

This package provides attributes to automatically register routes. Here's a quick example:

```php
namespace App\Http\Controllers\Backend;

use Annotation\Route\Domain;
use Annotation\Route\Group;
use Annotation\Route\Prefix;
use Annotation\Route\Route\Get;

class HomeController extends Controller
{
    #[Get('index', 'index')]
    public function index(Request $request)
    {
        //
    }
}
```

This attribute will automatically register this route:

```php
use App\Http\Controllers\Backend\HomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('backend/home')
    ->name('backend.home.')
    ->group(function () {
        Route::get('index', [HomeController::class, 'index'])->name('index');
    });
```

<!-- INSTALLATION -->

## Installation

You can install the package via [Composer]:

```bash
composer require annotation/routing
```

You can publish the config file with:

```shell
php artisan vendor:publish --provider="Annotation\Routing\RouteServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Automatic Registration Routes
    |--------------------------------------------------------------------------
    |
    | Automatic registration of routes will only happen if this setting is `true`
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Automatically Registered Paths
    |--------------------------------------------------------------------------
    |
    | Controllers in these directories that have routing attributes will automatically be registered.
    | Optionally, you can specify group configuration by using key/values
    |
    */

    'directories' => [
        app_path('Http/Controllers'),
        app_path('Http/Controllers/Web') => [
            'middleware' => ['web'],
        ],
        app_path('Http/Controllers/Api') => [
            'prefix' => 'api',
            'middleware' => 'api',
        ],
    ],
];
```

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- USAGE EXAMPLES -->

## Usage

The package provides several annotations that should be put on controller classes and methods. These annotations will be used to automatically register routes.

### Basic Usage

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;

class MyController extends Controller
{
    #[Get('route')]
    public function myMethod()
    {
        //
    }
}
```

This attribute will automatically register this route:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->name('my.my-method');
```

### Specifying Prefix

You can use the `Prefix` annotation on a class to prefix the routes of all methods of that class.

```php
namespace App\Http\Controllers;

use Annotation\Route\Prefix;
use Annotation\Route\Route\Get;

#[Prefix('prefix')]
class MyController
{
    #[Get('route')]
    public function myMethod()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('prefix')->name('my.my-method');
```

### Specify Named

All HTTP verb attributes accept a parameter named `name` that accepts a route name.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;

class MyController
{
    #[Get('route', name: 'route')]
    public function myMethod()
    {
    }
}
```

This attribute will automatically register this route:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->name('my.route');
```

### Using other HTTP verbs

We have left no HTTP verb behind. You can use these attributes on controller methods.

```php
#[Annotation\Route\Route\Post('uri')]
#[Annotation\Route\Route\Put('uri')]
#[Annotation\Route\Route\Patch('uri')]
#[Annotation\Route\Route\Delete('uri')]
#[Annotation\Route\Route\Options('uri')]
```

### Using multiple verbs

To register a route for all verbs, you can use the `Any` attribute:

```php
#[Annotation\Route\Route\Any('uri')]
```

To register a route for a few verbs at once, you can use the `Route` attribute directly:

```php
#[Annotation\Route\Route(['put', 'patch'], 'uri')]
```

### Specifying Middleware

All HTTP verb attributes accept a parameter named `middleware` that accepts a middleware class or an array of middleware classes.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;

class MyController
{
    #[Get('route', middleware: MyMiddleware::class)]
    public function myMethod()
    {
    }
}
```

This annotation will automatically register this route:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->middleware(MyMiddleware::class);
```

To apply middleware on all methods of a class you can use the Middleware attribute. You can mix this with applying attribute on a method.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\Middleware;
use Annotation\Route\WithoutMiddleware;

#[Middleware(GlobalMiddleware::class)]
class MyController
{
    #[Get('route', middleware: MyMiddleware::class)]
    public function myMethod()
    {
    }
    
    #[Get('global-middleware')]
    public function globalMiddleware()
    {
    }
    
    #[Get('without-middleware', withoutMiddleware: GlobalMiddleware::class)]
    // or
    // #[WithoutMiddleware(GlobalMiddleware::class)]
    public function withoutMiddleware()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->middleware([GlobalMiddleware::class, MyMiddleware::class]);
Route::get('global-middleware', [MyController::class, 'globalMiddleware'])->prefix('my')->middleware(GlobalMiddleware::class);
Route::get('without-middleware', [MyController::class, 'withoutMiddleware'])->prefix('my');
```

### Specifying Domain

You can use the `Domain` annotation on a class to prefix the routes of all methods of that class.

```php
namespace App\Http\Controllers;

use Annotation\Route\Domain;
use Annotation\Route\Route\Get;

#[Domain('subdomain.localhost')]
class MyController
{
    #[Get('route')]
    public function myMethod()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->domain('subdomain.localhost');
```

### Specify Config

There maybe a need to define a domain from a configuration file, for example where your subdomain will be different on your development environment to your production environment.

```php
// config/app.php
return [
    'url' => env('APP_URL', 'http://localhost'),
];
```

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\Routing\Config;

#[Config('app.url', '127.0.0.1')]
class MyController
{
    #[Get('route')]
    public function myMethod()
    {
    }
}
```

When this is parsed, it will get the value of `app.url` from the config file and register the route as follows:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->domain('localhost');
```

If `app.url` does not exist and register the route as follows:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->domain('127.0.0.1');
```

### Specifying ScopeBindings

When implicitly binding multiple Eloquent models in a single route definition, you may wish to scope the second Eloquent model such that it must be a child of the previous Eloquent model.

By adding the `ScopeBindings` annotation, you can enable this behaviour:

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\ScopeBindings;

class MyController
{
    #[Get('users/{user}/posts/{post}')]
    #[ScopeBindings]
    public function myMethod(User $user, Post $post)
    {
    }
}
```

This is akin to using the `->scopeBindings()` method on the route registrar manually:

```php
Route::get('users/{user}/posts/{post}', [MyController::class, 'myMethod'])->prefix('my')->scopeBindings();
```

By default, Laravel will enabled scoped bindings on a route when using a custom keyed implicit binding as a nested route parameter, such as `/users/{user}/posts/{post:slug}`.

To disable this behaviour, you can pass `false` to the attribute:

```php
#[Annotation\Route\ScopeBindings(false)]
```

This is the equivalent of calling `->withoutScopedBindings()` on the route registrar manually.

You can also use the annotation on controllers to enable implicitly scoped bindings for all its methods. For any methods where you want to override this, you can pass `false` to the attribute on those methods, just like you would normally.

### Specifying Where

You can use the `Where` annotation on a class or method to constrain the format of your route parameters.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\Route\Post;
use Annotation\Route\Routing\WhereAlphaNumeric;
use Annotation\Route\Where;

#[Where('custom', '[0-9]+')]
class MyController
{
    #[Get('route/{custom}')]
    public function myMethod()
    {
    }

    #[Post('post-route/{custom}/{alpha-numeric}')]
    #[WhereAlphaNumeric('alpha-numeric')]
    public function myPostMethod()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route/{custom}', [MyController::class, 'myMethod'])->prefix('my')->where(['custom' => '[0-9]+']);
Route::post('post-route/{custom}/{alpha-numeric}', [MyController::class, 'myPostMethod'])->prefix('my')->where(['custom' => '[0-9]+', 'alpha-numeric' => '[a-zA-Z0-9]+']);
```

For convenience, some commonly used regular expression patterns have helper attributes that allow you to quickly add pattern constraints to your routes.

```php
#[Annotation\Route\Routing\WhereAlpha('alpha')]
#[Annotation\Route\Routing\WhereAlphaNumeric('alpha-numeric')]
#[Annotation\Route\Routing\WhereIn('in', ['value1', 'value2'])]
#[Annotation\Route\Routing\WhereNumber('number')]
#[Annotation\Route\Routing\WhereUlid('ulid')]
#[Annotation\Route\Routing\WhereUuid('uuid')]
```

### Specifying Group

You can use the `Group` annotation on a class to create multiple groups with different domains and prefixes for the routes of all methods of that class.

```php
namespace App\Http\Controllers;

use Annotation\Route\Group;
use Annotation\Route\Route\Get;

#[Group(domain: 'domain.localhost', prefix: 'domain')]
#[Group(domain: 'subdomain.localhost', prefix: 'subdomain')]
class MyController
{
    #[Get('route')]
    public function myMethod()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('domain')->domain('domain.localhost');
Route::post('route', [MyController::class, 'myMethod'])->prefix('subdomain')->domain('subdomain.localhost');
```

### Specifying Defaults

You can use the `Defaults` annotation on a class or method to define the default values of your optional route parameters.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\Route\Post;
use Annotation\Route\Routing\Defaults;

#[Defaults('param', 'default')]
class MyController
{
    #[Get('route/{param?}')]
    public function myMethod($param)
    {
    }

    #[Post('route/{param?}/{param2?}')]
    #[Defaults('param2', 'post-default')]
    public function myPostMethod($param, $param2)
    {
    }

    #[Get('override-route/{param?}')]
    #[Defaults('param', 'override-default')]
    public function myOverrideMethod($param)
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route/{param?}', [MyController::class, 'myMethod'])->prefix('my')->setDefaults(['param', 'default']);
Route::post('route/{param?}/{param2?}', [MyController::class, 'myPostMethod'])->prefix('my')->setDefaults(['param', 'default', 'param2' => 'post-default']);
Route::get('override-route/{param?}', [MyController::class, 'myOverrideMethod'])->prefix('my')->setDefaults(['param', 'override-default']);
```

### Specifying WithTrashed

- You can use the `WithTrashed` annotation on a class or method to enable WithTrashed bindings to the model.
- You can explicitly override the behaviour using `WithTrashed(false)` if it is applied at the class level.

```php
namespace App\Http\Controllers;

use Annotation\Route\Route\Get;
use Annotation\Route\Route\Post;
use Annotation\Route\WithTrashed;

#[WithTrashed]
class MyController
{
    #[Get('route')]
    #[WithTrashed]
    public function myMethod()
    {
    }

    #[Post('route')]
    #[WithTrashed(false)]
    public function myPostMethod()
    {
    }

    #[Get('default-route')]
    public function myDefaultMethod()
    {
    }
}
```

These annotations will automatically register these routes:

```php
Route::get('route', [MyController::class, 'myMethod'])->prefix('my')->WithTrashed();
Route::post('route', [MyController::class, 'myPostMethod'])->prefix('my')->withTrashed(false);
Route::get('default-route', [MyController::class, 'myDefaultMethod'])->prefix('my')->withTrashed();
```

### Resource Controllers

To register a [resource controller](https://laravel.com/docs/controllers#resource-controllers), use the `Resource` attribute as shown in the example below.

- You can use `only` or `except` parameters to manage your resource routes availability.
- You can use `parameters` parameter to modify the default parameters set by the resource attribute.
- You can use the `names` parameter to set the route names for the resource controller actions. Pass a string value to set a base route name for each controller action or pass an array value to define the route name for each controller action.
- You can use `shallow` parameter to make a nested resource to apply nesting only to routes without a unique child identifier (`index`, `create`, `store`).
- You can use `apiResource` boolean parameter to only include actions used in APIs. Alternatively, you can use the `ApiResource` attribute, which extends the `Resource` attribute class, but the parameter `apiResource` is already set to `true`.
- Using `Resource` attribute with `Domain`, `Prefix` and `Middleware` attributes works as well.

```php
namespace App\Http\Controllers;

use Annotation\Route\Prefix;
use Annotation\Route\Resource;
use Illuminate\Http\Request;

#[Prefix('api/v1')]
#[Resource(
    resource: 'photos.comments',
    apiResource: true,
    except: ['destroy'],
    names: 'api.v1.photo-comments',
    parameters: ['comments' => 'comment:uuid'],
    shallow: true,
)]
class PhotoCommentController
{
    public function index($photo)
    {}

    public function store(Request $request, $photo)
    {}

    public function show($comment)
    {}

    public function update(Request $request, $comment)
    {}
}
```

The attribute in the example above will automatically register following routes:

```php
Route::get('api/v1/comments/{comment}', [PhotoCommentController::class, 'show'])->name('api.v1.photo-comments.show');
Route::match(['put', 'patch'], 'api/v1/comments/{comment}', [PhotoCommentController::class, 'update'])->name('api.v1.photo-comments.update');
Route::get('api/v1/photos/{photo}/comments', [PhotoCommentController::class, 'index'])->name('api.v1.photo-comments.index');
Route::post('api/v1/photos/{photo}/comments', [PhotoCommentController::class, 'store'])->name('api.v1.photo-comments.store');
```

<!-- CONTRIBUTING -->

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- CONTRIBUTORS -->

## Contributors

Thanks goes to these wonderful people:

<a href="https://github.com/dependencies-packagist/routing/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=dependencies-packagist/routing" alt="contrib.rocks image" />
</a>

Contributions of any kind are welcome!

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- LICENSE -->

## License

Distributed under the project_license. Please see [License File] for more information.

<p align="right">[<a href="#readme-top">back to top</a>]</p>

[GitHub Tag]: https://img.shields.io/github/v/tag/dependencies-packagist/routing

[Total Downloads]: https://img.shields.io/packagist/dt/annotation/routing?style=flat-square

[Packagist Version]: https://img.shields.io/packagist/v/annotation/routing

[Packagist PHP Version Support]: https://img.shields.io/packagist/php-v/annotation/routing

[Packagist License]: https://img.shields.io/github/license/dependencies-packagist/routing

[Packagist URL]: https://packagist.org/packages/annotation/routing

[Repository URL]: https://github.com/dependencies-packagist/routing

[GitHub Open Issues]: https://github.com/dependencies-packagist/routing/issues

[GitHub Tag URL]: https://github.com/dependencies-packagist/routing/tagsv

[License File]: https://github.com/dependencies-packagist/routing/blob/main/LICENSE

[Composer]: https://getcomposer.org
