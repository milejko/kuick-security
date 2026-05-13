# Kuick Security — Copilot Instructions

## Commands

```bash
# Run all checks (CS, Stan, MD, unit tests with coverage)
composer test:all

# Individual checks
composer test:phpcs        # PSR-12 code style
composer test:phpstan      # Static analysis (level 9)
composer test:phpmd        # Mess detection
composer test:phpunit      # Unit tests with coverage

# Auto-fix code style
composer fix:phpcbf

# Run a single test class
XDEBUG_MODE=coverage vendor/bin/phpunit tests/Unit/GuardhouseTest.php

# Run a single test method
XDEBUG_MODE=coverage vendor/bin/phpunit --filter testAddingAndMatchingASingleGuard

# CI (Docker-based, mirrors GitHub Actions)
make test PHP_VERSION=8.4
```

PHPUnit requires `XDEBUG_MODE=coverage` (set explicitly; `requireCoverageMetadata="true"` is enforced).

## Architecture

This is a tiny PHP library (4 source files) providing PSR-15 security middleware.

```
Guardhouse          – registers guard callables per path+HTTP method; matches them against a ServerRequest
  └─ ExecutableGuard  – internal value object holding (path, callable guard, methods[]);
                        merges regex path captures into request query params before invoking the guard
SecurityMiddleware  – PSR-15 MiddlewareInterface; delegates to Guardhouse.matchGuards(), then calls each matched guard
SecurityException   – base exception (currently unused in favour of Kuick\Http\HttpException thrown by guards)
```

**Request flow:**
1. `SecurityMiddleware::process()` calls `Guardhouse::matchGuards($request)`.
2. `Guardhouse` iterates registered guards, matching by regex path and HTTP method.
3. Each matching `ExecutableGuard::execute()` invokes the callable with the request (query params enriched with regex captures).
4. Guards signal failure by throwing `Kuick\Http\HttpException`; returning `null`/`void` means the guard passed.
5. If all guards pass, `$handler->handle($request)` continues the middleware chain.

## Key Conventions

### Guards are plain invokable objects
A guard is any `object` with `__invoke(ServerRequestInterface $request): void|null`. No interface required. Throw `Kuick\Http\HttpException` to deny the request.

### Path patterns are full regexes
Paths registered with `Guardhouse::addGuard()` are treated as regex patterns anchored with `#^…$#`. Named/unnamed capture groups in the pattern are merged into the request's query params.

### HEAD is implicit with GET
When a guard is registered for `GET`, `HEAD` is automatically added to its method list inside `matchGuards()`.

### PSR-12 + PHPStan level 9
All code must pass PHPStan at level 9 (`--memory-limit=512M`). Static analysis failures block CI. Use `XDEBUG_MODE=off` when running PHPStan.

### Test structure
- Tests live in `tests/Unit/`; namespace `Tests\Kuick\Unit\Security\`.
- Mocks live in `tests/Unit/Mocks/`; namespace `Tests\Kuick\Security\Unit\Mocks\` (note the namespace differs from the test namespace).
- Each test class targets one source class via `@covers \Kuick\Security\ClassName`.
- `Psr\Log\NullLogger` is used wherever a `LoggerInterface` is needed in tests.
- `Nyholm\Psr7\ServerRequest` is used to construct PSR-7 requests in tests.
- PHPUnit is configured with `failOnRisky="true"` and `failOnWarning="true"` — avoid output, risky assertions, or missing coverage metadata.

### Dependencies
- `kuick/http` provides `HttpException`, `Response`, `JsonResponse`, and `RequestInterface` constants — prefer these over raw PSR-7 types where applicable.
- `psr/log ^3.0` is a runtime dependency; always accept `LoggerInterface` via constructor injection.
- Dev tooling comes from `kuick/qa-toolkit` (bundles PHPUnit, PHPStan, PHPCS, PHPMD).
