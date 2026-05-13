# Kuick Security
[![Latest Version](https://img.shields.io/github/release/milejko/kuick-security.svg?cacheSeconds=3600)](https://github.com/milejko/kuick-security/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4%20|%208.5-blue?logo=php&cacheSeconds=3600)](https://www.php.net)
[![Total Downloads](https://img.shields.io/packagist/dt/kuick/security.svg?cacheSeconds=3600)](https://packagist.org/packages/kuick/security)
[![GitHub Actions CI](https://github.com/milejko/kuick-security/actions/workflows/ci.yml/badge.svg)](https://github.com/milejko/kuick-security/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/milejko/kuick-security/graph/badge.svg?token=M3FW3XYJ5J)](https://codecov.io/gh/milejko/kuick-security)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?cacheSeconds=14400)](LICENSE)

## Security package implementing PSR-15 middleware

### Key features
1. PSR-15(https://www.php-fig.org/psr/psr-15/) security middleware implementation
2. Support for flexible Guards (any callable)
3. Guardhouse service with methods to register Guards (regex path support)

### Installation

```bash
composer require kuick/security
```

### Usage

#### 1. Create a guard

A guard is any invokable object (or closure) accepting a `ServerRequestInterface` and returning `void|null`.
Throw a `Kuick\Http\HttpException` to deny the request.

```php
use Kuick\Http\HttpException;
use Kuick\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

class BearerTokenGuard
{
    public function __invoke(ServerRequestInterface $request): void
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer valid-token')) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid or missing token');
        }
    }
}
```

#### 2. Register guards in the `Guardhouse`

Use `addGuard(string $path, object $guard, array $methods = [...])` to register guards.
The `$path` is a **full regex** (anchored as `#^…$#`). Named capture groups are merged into the request's query params.

By default (when `$methods` is omitted), a guard matches all HTTP methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`. `HEAD` is automatically included whenever `GET` is listed.

```php
use Kuick\Security\Guardhouse;
use Psr\Log\NullLogger;

$guardhouse = (new Guardhouse(new NullLogger()))
    // protect all routes with a token check
    ->addGuard('/api/.*', new BearerTokenGuard())
    // restrict a specific route to GET only
    ->addGuard('/api/resource/(?P<id>\d+)', new BearerTokenGuard(), ['GET']);
```

#### 3. Wire up the PSR-15 middleware

Pass the `Guardhouse` to `SecurityMiddleware` and add it to your PSR-15 middleware stack.

```php
use Kuick\Security\SecurityMiddleware;

$middleware = new SecurityMiddleware($guardhouse, new NullLogger());

// Example with any PSR-15-compatible dispatcher (e.g. Relay, Slim, etc.)
$response = $middleware->process($serverRequest, $nextHandler);
```

If a guard throws a `Kuick\Http\HttpException` the exception propagates up — your framework's error handler is responsible for converting it into an HTTP response. If all guards pass, the request is forwarded to `$nextHandler`.

#### Path regex & captured parameters

Regex captures (named or positional) from the matched path are merged into the request's query params before the guard is invoked:

```php
// Guard registered for: '/users/(?<userId>\d+)'
// Request: GET /users/42
// Inside the guard, $request->getQueryParams()['userId'] === '42'
```
