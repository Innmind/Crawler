<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\OpenGraph;
use Innmind\Xml\Node;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Set,
    Sequence,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class OpenGraphTest extends TestCase
{
    public function testReturnEmptySetWhenHeadNotFound()
    {
        $node = $this->createMock(Node::class);
        $node
            ->expects($this->any())
            ->method('children')
            ->willReturn(Sequence::of(Node::class));

        $values = (new OpenGraph('image'))($node);

        $this->assertTrue($values->equals(Set::of('string')));
    }

    public function testReturnEmptySetWhenNoPropertyFound()
    {
        $html = Stream::ofContent(<<<HTML
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
        $html = Stream::ofContent(<<<HTML
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
