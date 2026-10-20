<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\ExceptionListener;
use App\Exception\FileTooLargeException;
use App\Exception\InvalidFileException;
use App\Exception\InvalidLineException;
use App\Exception\UnsupportedFileTypeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends TestCase
{
    /** @return iterable<string, array{\Throwable, int, string, ?int}> */
    public static function conversionExceptions(): iterable
    {
        yield 'fichier invalide' => [new InvalidFileException('Fichier vide'), 400, 'invalid_file', null];
        yield 'ligne invalide' => [new InvalidLineException(3, 'Date invalide'), 422, 'invalid_line', 3];
        yield 'fichier trop gros' => [new FileTooLargeException(2 * 1024 * 1024), 413, 'file_too_large', null];
        yield 'type non supporté' => [new UnsupportedFileTypeException(['txt']), 415, 'unsupported_file_type', null];
    }

    #[DataProvider('conversionExceptions')]
    public function testConversionExceptionIsConvertedToJson(\Throwable $exception, int $status, string $code, ?int $line): void
    {
        $event = $this->event($exception);

        (new ExceptionListener())($event);

        $response = $event->getResponse();
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(
            ['error' => ['code' => $code, 'message' => $exception->getMessage(), 'line' => $line]],
            json_decode($response->getContent(), true),
        );
    }

    public function testOtherExceptionsAreLeftToSymfony(): void
    {
        $event = $this->event(new NotFoundHttpException());

        (new ExceptionListener())($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUnexpectedErrorsAreNotExposed(): void
    {
        $event = $this->event(new \RuntimeException('secret : /var/www/config.php'));

        (new ExceptionListener())($event);

        $this->assertFalse($event->hasResponse());
    }

    private function event(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/api/conversions'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
