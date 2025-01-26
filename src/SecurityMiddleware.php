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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->debug('Executing guards for path: ' . $request->getUri()->getPath());
        $guardsResponse = $this->executeGuards($request);
        // return guards response if it exists
        if (null !== $guardsResponse) {
            return $guardsResponse;
        }
        // otherwise, continue with the next middleware
        return $handler->handle($request);
    }

    private function executeGuards(ServerRequestInterface $request): ?ResponseInterface
    {
        // execute guards
        foreach ($this->guardhouse->matchGuards($request) as $executableGuard) {
            $guardClass = get_class($executableGuard->guard);
            $guardResponse = $executableGuard->execute($request);
            // if guard generates a response, return it
            if (null !== $guardResponse) {
                return $guardResponse;
            }
            $this->logger->info('Guard passed: ' . $guardClass);
        }
        return null;
    }
}
