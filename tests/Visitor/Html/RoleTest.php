<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\{
    Visitor\Html\Role,
    Exception\DomainException,
};
use Innmind\Xml\Element;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testInterface()
    {
        $visitor = new Role('main');

        $html = html()(
            Stream::ofContent(<<<HTML
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

        $this->assertInstanceOf(Set::class, $elements);
        $this->assertSame(Element::class, (string) $elements->type());
        $this->assertCount(2, $elements);
        $elements = unwrap($elements);

        if (PHP_OS === 'Darwin') { // don't why there is a difference between OSes
            $this->assertSame(
                "\n".'            <h1>whatever</h1>'."\n".'        ',
                \current($elements)->content()
            );
        } else {
            $this->assertSame(
                '<h1>whatever</h1>'."\n".'        ',
                \current($elements)->content()
            );
        }

        \next($elements);
        $this->assertSame('hey', \current($elements)->content());
    }

    public function testThrowWhenEmptyRole()
    {
        $this->expectException(DomainException::class);

        new Role('');
    }
}
