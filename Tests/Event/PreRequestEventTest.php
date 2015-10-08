<?php

namespace Innmind\Crawler\Tests\Event;

use Innmind\Crawler\Event\PreRequestEvent;
use GuzzleHttp\Message\Request;

class PreRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRequest()
    {
        $e = new PreRequestEvent($r = new Request('GET', ''));

        $this->assertSame($r, $e->getRequest());
    }
}
