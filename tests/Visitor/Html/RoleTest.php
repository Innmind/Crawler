<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\Role;
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\{
    NodeInterface,
    ElementInterface,
    Translator\NodeTranslator,
    Translator\NodeTranslators
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\SetInterface;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $visitor = new Role('main');

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
        <article role="main">
            <h1>whatever</h1>
        </article>
    </div>
    <script></script>
    <div role="main">hey</div>
</body>
</html>
HTML
            )
        );

        $elements = $visitor($html);

        $this->assertInstanceOf(SetInterface::class, $elements);
        $this->assertSame(ElementInterface::class, (string) $elements->type());
        $this->assertCount(2, $elements);
        $this->assertSame(
            '<h1>whatever</h1>'."\n".'        ',
            $elements->current()->content()
        );
        $elements->next();
        $this->assertSame('hey', $elements->current()->content());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyRole()
    {
        new Role('');
    }
}
