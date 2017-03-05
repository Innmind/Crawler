<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\RemoveComments;
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
use PHPUnit\Framework\TestCase;

class RemoveCommentsTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new RemoveComments;

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
        <!-- some comment -->
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
            <script>some nasty javascript</script><h2>else</h2>
            <script>some nasty javascript</script><h2>else</h2>
            <script>some nasty javascript</script><h2>else</h2>
        </article></div>
    <script></script><div>hey</div>
</body></html>
HTML;

        $this->assertNotSame($html, $cleaned);
        $this->assertInstanceOf(NodeInterface::class, $cleaned);
        $this->assertSame($expected, (string) $cleaned);
    }
}
