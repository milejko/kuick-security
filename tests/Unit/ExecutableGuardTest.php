<?php

namespace Tests\Kuick\Unit\Security;

use Kuick\Http\Message\JsonResponse;
use PHPUnit\Framework\TestCase;
use Kuick\Security\ExecutableGuard;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Kuick\Security\ExecutableGuard
 */
class ExecutableGuardTest extends TestCase
{
    public function testIfEmptyGuardPassesSilently(): void
    {
        $okGuardMock = function (): void {
        };
        //should do nothing (no exception should be raised)
        $executableGuard = new ExecutableGuard('/test', $okGuardMock, ['GET']);
        $executableGuard->execute(new ServerRequest('GET', '/test', []));
        $this->assertTrue(true);
    }

    public function testIfFailingGuardRaisesException(): void
    {
        $failingGuardMock = function (ServerRequestInterface $request): ?ResponseInterface {
            if ($request->getQueryParams()['some-param'] !== 'some-value') {
                return null;
            }
            return new JsonResponse([], JsonResponse::HTTP_BAD_REQUEST);
        };
        $failedGuard = (new ExecutableGuard('/test', $failingGuardMock, ['GET']))
            ->setParams(['some-param' => 'some-value']);

        $response = $failedGuard->execute(new ServerRequest('GET', '/test', [], 'ooops'));
        $this->assertEquals(400, $response->getStatusCode());
    }
}
