<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\FindContentNode;
use Innmind\Html\{
    Reader\Reader,
    Visitor\Body,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\{
    NodeInterface,
    Translator\NodeTranslator,
    Translator\NodeTranslators
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class FindContentNodeTest extends TestCase
{
    public function testFind()
    {
        $reader = new Reader(
            new NodeTranslator(
                NodeTranslators::defaults()->merge(
                    HtmlTranslators::defaults()
                )
            )
        );
        $html = $reader->read(new Stream(fopen('fixtures/lemonde.html', 'r')));

        $node = (new FindContentNode)(
            (new Map('int', NodeInterface::class))
                ->put(
                    0,
                    (new Body)($html)
                )
        );

        $this->assertSame('div', $node->name());
        $this->assertCount(1, $node->attributes());
        $this->assertSame(
            'container_18 clearfix',
            $node->attributes()->get('class')->value()
        );
    }

    public function testDoesntFailWhenCursorNotAtStart()
    {
        $expected = $this->createMock(NodeInterface::class);
        $expected
            ->expects($this->once())
            ->method('hasChildren')
            ->willReturn(false);
        $map = (new Map('int', NodeInterface::class))
            ->put(0, $expected);
        $map->next();

        $node = (new FindContentNode)($map);

        $this->assertSame($expected, $node);
    }
}
