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
    use HtmlTrait;

    private $read;
    private $resolver;

    public function __construct(
        Reader $read,
        UrlResolver $resolver
    ) {
        $this->read = $read;
        $this->resolver = $resolver;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $links = $links
            ->filter(function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(function(Link $link): bool {
                return $link->relationship() === 'alternate' &&
                    $link->attributes()->contains('hreflang');
            });

        if ($links->size() === 0) {
            return $attributes;
        }

        $alternates = $links
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(MapInterface $links, Link $link): MapInterface {
                    return $links->put(
                        $link->href(),
                        $link->attributes()->get('hreflang')->value()
                    );
                }
            )
            ->groupBy(function(UrlInterface $url, string $language) {
                return $language;
            })
            ->map(function(string $language, MapInterface $links) use ($request, $attributes): MapInterface {
                return $links->map(function(UrlInterface $link, string $language) use ($request, $attributes): Pair {
                    $link = $this->resolver->resolve(
                        $request,
                        $attributes,
                        $link
                    );

                    return new Pair($link, $language);
                });
            })
            ->reduce(
                new Map('string', Attribute::class),
                function(MapInterface $languages, string $language, MapInterface $links): MapInterface {
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
