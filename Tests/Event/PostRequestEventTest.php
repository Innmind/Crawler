<?php

namespace Innmind\Crawler\Tests\Event;

use Innmind\Crawler\Event\PostRequestEvent;
use Innmind\Crawler\Event\PreRequestEvent;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

class PostRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        $e = new PostRequestEvent(new Request('GET', ''), $r = new Response(200));

        $this->assertInstanceOf(PreRequestEvent::class, $e);
        $this->assertSame($r, $e->getResponse());
    }
}
