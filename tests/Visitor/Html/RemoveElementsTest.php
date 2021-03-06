<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\RemoveElements;
use Innmind\Xml\Node;
use Innmind\Stream\Readable\Stream;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class RemoveElementsTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new RemoveElements('script');

        $html = html()(
            Stream::ofContent(<<<HTML
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

        if (\PHP_OS === 'Darwin') { // don't why there is a difference between OSes
            $expected = "<!DOCTYPE html>\n".
                        "<html>\n".
                        "<body>\n".
                        "    <div>\n".
                        "        <article>\n".
                        "            <h1>whatever</h1>\n".
                        "            \n".
                        "            <h2>else</h2>\n".
                        "            \n".
                        "            <h2>else</h2>\n".
                        "            \n".
                        "            <h2>else</h2>\n".
                        "        </article>\n".
                        "    </div>\n".
                        "    \n".
                        "    <div>hey</div>\n".
                        "</body>\n".
                        '</html>';
        } else {
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
        }

        $this->assertNotSame($html, $cleaned);
        $this->assertInstanceOf(Node::class, $cleaned);
        $this->assertSame($expected, $cleaned->toString());
    }
}
