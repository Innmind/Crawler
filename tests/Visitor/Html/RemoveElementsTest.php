<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\RemoveElements;
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\{
    NodeInterface,
    Translator\NodeTranslator,
    Translator\NodeTranslators
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class RemoveElementsTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new RemoveElements(
            (new Set('string'))->add('script')
        );

        $reader = new Reader(
            new NodeTranslator(
                NodeTranslators::defaults()->merge(
                    HtmlTranslators::defaults()
                )
            )
        );
        $html = $reader->read(
            new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<body>
    <div>
        <article>
            <h1>whatever</h1>
            <script>some nasty javascript</script>
            <h2>else</h2>
            <script>some nasty javascript</script>
            <h2>else</h2>
            <script>some nasty javascript</script>
            <h2>else</h2>
        </article>
    </div>
    <script></script>
    <div>hey</div>
</body>
</html>
HTML
            )
        );

        $cleaned = $visitor($html);
        $expected = <<<HTML
<!DOCTYPE html>
<html><body>
    <div>
        <article><h1>whatever</h1>
            <h2>else</h2>
            <h2>else</h2>
            <h2>else</h2>
        </article></div>
    <div>hey</div>
</body></html>
HTML;

        $this->assertNotSame($html, $cleaned);
        $this->assertInstanceOf(NodeInterface::class, $cleaned);
        $this->assertSame($expected, (string) $cleaned);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type SetInterface<string>
     */
    public function testThrowWhenInvalidNames()
    {
        new RemoveElements(new Set('int'));
    }
}
