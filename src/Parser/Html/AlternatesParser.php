<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute,
    HttpResource\Alternate,
    HttpResource\Alternates,
    UrlResolver,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Element\Link,
    Exception\ElementNotFound,
};
use Innmind\Xml\{
    Reader,
    Node,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SequenceInterface,
    Pair,
    Set,
};

final class AlternatesParser implements Parser
{
    private $read;
    private $resolve;

    public function __construct(
        Reader $read,
        UrlResolver $resolve
    ) {
        $this->read = $read;
        $this->resolve = $resolve;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $document = ($this->read)($response->body());

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $links = $links
            ->filter(static function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(static function(Link $link): bool {
                return $link->relationship() === 'alternate' &&
                    $link->attributes()->contains('hreflang');
            });

        if ($links->size() === 0) {
            return $attributes;
        }

        $alternates = $links
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                static function(MapInterface $links, Link $link): MapInterface {
                    return $links->put(
                        $link->href(),
                        $link->attributes()->get('hreflang')->value()
                    );
                }
            )
            ->groupBy(static function(UrlInterface $url, string $language) {
                return $language;
            })
            ->map(function(string $language, MapInterface $links) use ($request, $attributes): MapInterface {
                return $links->map(function(UrlInterface $link, string $language) use ($request, $attributes): Pair {
                    $link = ($this->resolve)(
                        $request,
                        $attributes,
                        $link
                    );

                    return new Pair($link, $language);
                });
            })
            ->reduce(
                new Map('string', Attribute::class),
                static function(MapInterface $languages, string $language, MapInterface $links): MapInterface {
                    return $languages->put(
                        $language,
                        new Alternate(
                            $language,
                            $links->keys()
                        )
                    );
                }
            );

        return $attributes->put(
            self::key(),
            new Alternates($alternates)
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }
}
