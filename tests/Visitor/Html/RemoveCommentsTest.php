<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\RemoveComments;
use Innmind\Xml\Node;
use Innmind\Filesystem\Stream\StringStream;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class RemoveCommentsTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new RemoveComments;

        $html = html()(
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
        $this->assertInstanceOf(Node::class, $cleaned);
        $this->assertSame($expected, (string) $cleaned);
    }
}
