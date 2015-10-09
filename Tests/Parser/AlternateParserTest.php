<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\AlternateParser;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class AlternateParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new AlternateParser(new UrlResolver([]));

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());
    }

    public function testParse()
    {
        $p = new AlternateParser(new UrlResolver([]));
        $response = new Response(
            200,
            [
                'Link' => '<http://fr.xn--example.com/>; rel="alternate"; hreflang="fr", <http://example.com/2>; rel="next"',
            ],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <link rel="alternate" hreflang="fr" href="/fr/" />
    <link rel="alternate" hreflang="en" href="/en/" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $return = $p->parse(
            $r = new Resource('http://xn--example.com/', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('alternates'));
        $this->assertSame(
            [
                'fr' => ['http://fr.xn--example.com/', 'http://xn--example.com/fr/'],
                'en' => ['http://xn--example.com/en/'],
            ],
            $r->get('alternates')
        );
    }

    public function testName()
    {
        $this->assertSame('alternate', AlternateParser::getName());
    }
}
