<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CanonicalParser;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CanonicalParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new CanonicalParser(new UrlResolver([]));

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());

        $return = $p->parse(
            $r = new Resource('http://xn--example.com/', 'text/html'),
            new Response(
                200,
                [],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>

</body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());
    }

    public function testParse()
    {
        $p = new CanonicalParser(new UrlResolver([]));
        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <link rel="canonical" href="/product/42" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $return = $p->parse(
            $r = new Resource('http://xn--example.com/?product_id=42', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('canonical'));
        $this->assertSame(
            'http://xn--example.com/product/42',
            $r->get('canonical')
        );
    }

    public function testName()
    {
        $this->assertSame('canonical', CanonicalParser::getName());
    }
}
