# Kuick Security
[![Latest Version](https://img.shields.io/github/release/milejko/kuick-security.svg?cacheSeconds=3600)](https://github.com/milejko/kuick-security/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-blue?logo=php&cacheSeconds=3600)](https://www.php.net)
[![Total Downloads](https://img.shields.io/packagist/dt/kuick/security.svg?cacheSeconds=3600)](https://packagist.org/packages/kuick/security)
[![GitHub Actions CI](https://github.com/milejko/kuick-security/actions/workflows/ci.yml/badge.svg)](https://github.com/milejko/kuick-security/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/milejko/kuick-security/graph/badge.svg?token=M3FW3XYJ5J)](https://codecov.io/gh/milejko/kuick-security)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?cacheSeconds=14400)](LICENSE)

## Security package implementing PSR-15 middleware

### Key features
1. PSR-15(https://www.php-fig.org/psr/psr-15/) middleware implementation
2. Support for Guards easily registered to the Guardhouse service
3. Listener prioritization
4. Support for wildcard listeners (ie. *, Prefix*)

### Examples
1. Registering listeners to the listener provider
```
<?php

use Kuick\Event\EventDispatcher;
use Kuick\Event\ListenerProvider;

$provider = new ListenerProvider();
$provider->registerListener(
    'some class name or pattern',
    function () {
        //handle the event
    }
);

$dispatcher = new EventDispatcher($provider);
// $dispatcher->dispatch(new SomeEvent());
```
2. Listener prioritization (using stdClass as an event)
```
<?php

use stdClass;
use Kuick\Event\EventDispatcher;
use Kuick\Event\ListenerPriority;
use Kuick\Event\ListenerProvider;

$provider = new ListenerProvider();
$provider->registerListener(
    stdClass::class,
    function (stdClass $event) {
        //handle the event
    },
    ListenerPriority::HIGH
);
$provider->registerListener(
    stdClass::class,
    function (stdClass $event) {
        //handle the event
    },
    ListenerPriority::LOW
);
$dispatcher = new EventDispatcher($provider);
// it should handle the event with high priority listener first
$dispatcher->dispatch(new stdClass());
```
3. Registering wildcard listeners (using stdClass as an event)
```
<?php

use stdClass;
use Kuick\Event\EventDispatcher;
use Kuick\Event\ListenerProvider;

$provider = new ListenerProvider();
$provider->registerListener(
    '*',
    function (object $event) {
        //handle the event
    }
);
$provider->registerListener(
    'std*',
    function (object $event) {
        //handle the event
    }
);
$dispatcher = new EventDispatcher($provider);
// it should match both listeners and run them sequentialy
$dispatcher->dispatch(new stdClass());
```