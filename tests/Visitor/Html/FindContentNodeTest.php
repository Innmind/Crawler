<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\FindContentNode;
use Innmind\Html\Visitor\Element;
use Innmind\Xml\Node;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Sequence;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class FindContentNodeTest extends TestCase
{
    public function testFind()
    {
        $html = html()(new Stream(\fopen('fixtures/lemonde.html', 'r')));

        $node = (new FindContentNode)(
            Sequence::of(Node::class, Element::body()($html)),
        );

        $this->assertSame('div', $node->name());
        $this->assertCount(1, $node->attributes());
        $this->assertSame(
            'container_18 clearfix',
            $node->attributes()->get('class')->value()
        );
    }
}
