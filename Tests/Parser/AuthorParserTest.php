<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\AuthorParser;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class AuthorParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new AuthorParser(new DomCrawlerFactory);
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('author'));

        $return = $this->p->parse(
            $r = new Resource('', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
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
        $this->assertFalse($r->has('author'));
    }

    /**
     * @dataProvider tags
     */
    public function testParse($tag)
    {
        $return = $this->p->parse(
            $r = new Resource('', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta name="$tag" content="me" />
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
        $this->assertTrue($r->has('author'));
        $this->assertSame('me', $r->get('author'));
    }

    public function testName()
    {
        $this->assertSame('author', AuthorParser::getName());
    }

    public function tags()
    {
        return [['author'], ['Author'], ['AUTHOR']];
    }
}
