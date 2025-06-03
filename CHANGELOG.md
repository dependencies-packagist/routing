# CHANGELOG

## [v2.1.0](https://github.com/dependencies-packagist/routing/compare/v2.0.0...v2.1.0) - 2025-06-04

<details>
    <summary>Support Unified Gateway Endpoint</summary>

```php
Route::gateway('gateway.do');
```

By default, the `action` route parameters and `version` parameters are dynamically obtained from the `POST` request.

```php
Route::gateway('api', function (Request $request) {
    return $request->header('method');
}, function (Request $request) {
    return $request->header('version');
});
```

You can retrieve other fields from the `HEAD` request according to your needs.

</details>

## [v2.0.0](https://github.com/dependencies-packagist/routing/compare/v1.0.0...v2.0.0) - 2025-06-03

<details>
    <summary>Supporting Annotation Inheritance</summary>

```php
namespace App\Http\Controllers\Backend;

use Annotation\Route\Prefix;

#[Prefix('backend')]
class BaseController extends Controller
{}
```

```php
namespace App\Http\Controllers\Backend;

use Annotation\Route\Domain;
use Annotation\Route\Group;
use Annotation\Route\Prefix;
use Annotation\Route\Route\Get;

#[Group(prefix: 'home')]
class HomeController extends BaseController
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

</details>

## [v1.0.0](https://github.com/dependencies-packagist/routing/releases/tag/v1.0.0) - 2025-05-29

<details>
    <summary>The annotation only works in the current class scope</summary>

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

</details>

All notable changes to `annotation/routing` will be documented in this file.
