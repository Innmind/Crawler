<?php

namespace Innmind\Crawler\Tests\Event;

use Innmind\Crawler\Event\ParsingEvent;
use Innmind\Crawler\HttpResource;
use GuzzleHttp\Message\Response;

class ParsingEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        $e = new ParsingEvent(
            $hr = new HttpResource('', ''),
            $r = new Response(200)
        );

        $this->assertSame($hr, $e->getResource());
        $this->assertSame($r, $e->getResponse());
    }
}
