<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\OpenGraph;
use Innmind\Xml\Node;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Set;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class OpenGraphTest extends TestCase
{
    public function testReturnEmptySetWhenHeadNotFound()
    {
        $values = (new OpenGraph('image'))($this->createMock(Node::class));

        $this->assertTrue($values->equals(Set::of('string')));
    }

    public function testReturnEmptySetWhenNoPropertyFound()
    {
        $html = new StringStream(<<<HTML
<html>
<head>
    <meta property="og:title" content="The Rock" />
    <meta property="og:type" content="video.movie" />
    <meta property="og:url" content="http://www.imdb.com/title/tt0117500/" />
</head>
</html>
HTML
        );
        $node = html()($html);

        $values = (new OpenGraph('image'))($node);

        $this->assertTrue($values->equals(Set::of('string')));
    }

    public function testReturnValuesSet()
    {
        $html = new StringStream(<<<HTML
<html>
<head>
    <meta property="og:title" content="The Rock" />
    <meta property="og:type" content="video.movie" />
    <meta property="og:url" content="http://www.imdb.com/title/tt0117500/" />
    <meta property="og:image" content="http://ia.media-imdb.com/images/rock.jpg" />
    <meta property="og:image" content="http://ia.media-imdb.com/images/rock2.jpg" />
</head>
</html>
HTML
        );
        $node = html()($html);

        $values = (new OpenGraph('image'))($node);

        $this->assertTrue($values->equals(Set::of(
            'string',
            'http://ia.media-imdb.com/images/rock.jpg',
            'http://ia.media-imdb.com/images/rock2.jpg'
        )));
    }
}
