<?php

namespace App\EventListener;

use App\Exception\ConversionException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle Conversion Exceptions
        if (!$exception instanceof ConversionException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => $exception->getErrorCode(),
                'message' => $exception->getMessage(),
                'line' => $exception->getLineNumber(),
            ],
        ], $exception->getStatusCode()));
    }
}
