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
use Innmind\Filesystem\Stream\Stream;
use Innmind\Immutable\Map;

class FindContentNodeTest extends \PHPUnit_Framework_TestCase
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
        $html = $reader->read(Stream::fromPath('fixtures/lemonde.html'));

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
}
