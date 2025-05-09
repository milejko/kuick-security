<?php

namespace Tests\Kuick\Unit\Security;

use Kuick\Http\HttpException;
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
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Bad request');
        };
        $failedGuard = (new ExecutableGuard('/test', $failingGuardMock, ['GET']))
            ->setParams(['some-param' => 'some-value']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Bad request');
        $failedGuard->execute(new ServerRequest('GET', '/test', [], 'ooops'));
    }
}
