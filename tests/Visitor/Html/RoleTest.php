<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Visitor\Html\Role;
use Innmind\Xml\Element;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\SetInterface;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new Role('main');

        $html = html()(
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
        $this->assertSame(Element::class, (string) $elements->type());
        $this->assertCount(2, $elements);
        $this->assertSame(
            '<h1>whatever</h1>'."\n".'        ',
            $elements->current()->content()
        );
        $elements->next();
        $this->assertSame('hey', $elements->current()->content());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\DomainException
     */
    public function testThrowWhenEmptyRole()
    {
        new Role('');
    }
}
