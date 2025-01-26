<?php

namespace Tests\Kuick\Unit\Security;

use Kuick\Http\Message\Response;
use Kuick\Security\Guardhouse;
use Kuick\Security\SecurityMiddleware;
use Tests\Kuick\Security\Unit\Mocks\MockRequestHandler;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

/**
 * @covers \Kuick\Security\SecurityMiddleware
 */
class SecurityMiddlewareTest extends TestCase
{
    public function testIfEmptyGuardhousesFreelyPassesTheRequest(): void
    {
        $emptyGuardhouse = new Guardhouse(new NullLogger());
        $securityMiddleware = new SecurityMiddleware($emptyGuardhouse, new NullLogger());
        $response = $securityMiddleware->process(new ServerRequest('GET', '/test'), new MockRequestHandler());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getBody()->getContents());
    }

    public function testAddingAndMatchingMultipleGuards(): void
    {
        $putBlockingGuardMock = function (ServerRequestInterface $request): ?ResponseInterface {
            if ('PUT' !== $request->getMethod()) {
                return null;
            }
            return new Response(Response::HTTP_METHOD_NOT_ALLOWED, [], $request->getBody()->getContents());
        };
        $guardhouse = (new Guardhouse(new NullLogger()))
            ->addGuard('/sample', $putBlockingGuardMock, ['GET'])
            ->addGuard('/sample', $putBlockingGuardMock, ['POST'])
            ->addGuard('/test', $putBlockingGuardMock);

        $securityMiddleware = new SecurityMiddleware($guardhouse, new NullLogger());
        // nothing should happen by this point
        $securityMiddleware->process(new ServerRequest('GET', '/sample'), new MockRequestHandler());
        $securityMiddleware->process(new ServerRequest('POST', '/sample'), new MockRequestHandler());
        $securityMiddleware->process(new ServerRequest('PUT', '/sample'), new MockRequestHandler());

        $response = $securityMiddleware->process(new ServerRequest('PUT', '/test', [], 'oops'), new MockRequestHandler());
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('oops', $response->getBody()->getContents());
    }
}
