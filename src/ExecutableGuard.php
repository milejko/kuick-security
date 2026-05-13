<?php

/**
 * Kuick Framework (https://github.com/milejko/kuick)
 *
 * @link       https://github.com/milejko/kuick
 * @copyright  Copyright (c) 2010-2025 Mariusz Miłejko (mariusz@milejko.pl)
 * @license    https://en.wikipedia.org/wiki/BSD_licenses New BSD License
 */

namespace Kuick\Security;

use Kuick\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Executable guard
 */
class ExecutableGuard
{
    /** @var array<string, string> */
    private array $params = [];

    /** @phpstan-var (callable(ServerRequestInterface): (void|null))&object */
    public object $guard;

    /**
     * @phpstan-param (callable(ServerRequestInterface): (void|null))&object $guard
     * @param array<string> $methods
     */
    public function __construct(
        public readonly string $path,
        object $guard,
        public readonly array $methods = [
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_OPTIONS,
            RequestInterface::METHOD_POST,
            RequestInterface::METHOD_PUT,
            RequestInterface::METHOD_PATCH,
            RequestInterface::METHOD_DELETE,
        ],
    ) {
        $this->guard = $guard;
    }

    /**
     * @param array<string, string> $params
     */
    public function setParams(array $params = []): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Executes the guard
     * throws HttpException if the guard fails
     * @throws \Kuick\Http\HttpException
     */
    public function execute(ServerRequestInterface $request): void
    {
        ($this->guard)($request->withQueryParams(array_merge($this->params, $request->getQueryParams())));
    }
}
