<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\{
    Reader,
    Node,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Exception\ElementNotFound,
    Element\Link,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\first;

final class AndroidParser implements Parser
{
    private Reader $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $links = (new Elements('link'))(
                Element::head()($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var Set<Link>
         */
        $link = $links
            ->filter(static function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(static function(Link $link): bool {
                return $link->href()->scheme()->toString() === 'android-app';
            });

        if ($link->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), first($link)->href())
        );
    }

    public static function key(): string
    {
        return 'android';
    }
}
