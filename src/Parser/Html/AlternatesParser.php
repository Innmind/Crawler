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
    Visitor\Element,
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
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Pair,
    Set,
};

final class AlternatesParser implements Parser
{
    private Reader $read;
    private UrlResolver $resolve;

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
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $links = (new Elements('link'))(
                Element::head()($document),
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var Set<Link>
         */
        $links = $links
            ->filter(static function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(static function(Link $link): bool {
                return $link->relationship() === 'alternate' &&
                    $link->attributes()->contains('hreflang');
            });

        if ($links->empty()) {
            return $attributes;
        }

        /** @var Map<string, Attribute> */
        $alternates = $links
            ->toMapOf(
                Url::class,
                'string',
                static function(Link $link): \Generator {
                    yield $link->href() => $link->attributes()->get('hreflang')->value();
                },
            )
            ->map(function(Url $link, string $language) use ($request, $attributes): Pair {
                $link = ($this->resolve)(
                    $request,
                    $attributes,
                    $link,
                );

                return new Pair($link, $language);
            })
            ->groupBy(static function(Url $url, string $language) {
                return $language;
            })
            ->toMapOf(
                'string',
                Attribute::class,
                static function(string $language, Map $links): \Generator {
                    /** @var Map<Url, string> $links */
                    yield $language => new Alternate(
                        $language,
                        $links->keys(),
                    );
                },
            );

        return ($attributes)(
            self::key(),
            new Alternates($alternates),
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }
}
