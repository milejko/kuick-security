<?php

/**
 * Kuick Framework (https://github.com/milejko/kuick)
 *
 * @link       https://github.com/milejko/kuick
 * @copyright  Copyright (c) 2010-2025 Mariusz Miłejko (mariusz@milejko.pl)
 * @license    https://en.wikipedia.org/wiki/BSD_licenses New BSD License
 */

namespace Kuick\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class SecurityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Guardhouse $guardhouse,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Process the request and return a response.
     * Throws HttpException if one or more guards fail
     * @throws \Kuick\Http\HttpException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->debug('Executing guards for path: ' . $request->getUri()->getPath());
        // execute guards
        foreach ($this->guardhouse->matchGuards($request) as $executableGuard) {
            $executableGuard->execute($request);
            $this->logger->info('Guard passed: ' . get_class($executableGuard->guard));
        }
        // if no exception is thrown, continue to the next middleware
        return $handler->handle($request);
    }
}
