<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;

class HomeControllerTest extends AbstractTest
{
    public function testHomeRedirect(): void
    {
        $client = self::getClient();
        $client->request('GET', '/');

        $this->assertResponseRedirect();
        $client->followRedirect();
        $this->assertRouteSame('course_index');
    }
}