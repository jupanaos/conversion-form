<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class ConversionResponseFactory
{
    public function csv(string $csv, string $filename): Response
    {
        $response = new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename,
            preg_replace('/[^\x20-\x7e]|%/', '_', $filename),
        ));

        return $response;
    }
}
