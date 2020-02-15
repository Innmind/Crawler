<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    UrlResolver,
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

final class RssParser implements Parser
{
    private Reader $read;
    private UrlResolver $resolve;

    public function __construct(Reader $read, UrlResolver $resolve)
    {
        $this->read = $read;
        $this->resolve = $resolve;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             * @var Set<Link>
             */
            $links = (new Elements('link'))(
                Element::head()($document)
            )
                ->filter(static function(Node $link): bool {
                    return $link instanceof Link;
                })
                ->filter(static function(Link $link): bool {
                    return $link->relationship() === 'alternate' &&
                        $link->attributes()->contains('type') &&
                        $link->attributes()->get('type')->value() === 'application/rss+xml';
                });
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($links->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                ($this->resolve)(
                    $request,
                    $attributes,
                    first($links)->href()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'rss';
    }
}
