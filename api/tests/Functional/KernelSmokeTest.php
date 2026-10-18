<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KernelSmokeTest extends WebTestCase
{
    public function testKernelBootsAndUnknownRouteReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/unknown');

        $this->assertResponseStatusCodeSame(404);
    }
}
