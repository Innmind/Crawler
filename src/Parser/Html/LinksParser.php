<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    UrlResolver,
    Visitor\RemoveDuplicatedUrls,
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
    Element\A,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class LinksParser implements Parser
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
        $links = Set::of(Url::class);

        try {
            $links = (new Elements('link'))(
                Element::head()($document)
            )
                ->filter(static function(Node $link): bool {
                    return $link instanceof Link;
                })
                ->filter(static function(Link $link): bool {
                    return \in_array(
                        $link->relationship(),
                        ['first', 'next', 'previous', 'last'],
                        true
                    );
                })
                ->reduce(
                    $links,
                    static function(Set $links, Link $link): Set {
                        return $links->add($link->href());
                    }
                );
        } catch (ElementNotFound $e) {
            //pass
        }

        try {
            $links = (new Elements('a'))(
                Element::body()($document)
            )
                ->filter(static function(Node $a): bool {
                    return $a instanceof A;
                })
                ->filter(static function(A $a): bool {
                    return Str::of($a->toString())->substring(0, 1)->toString() !== '#';
                })
                ->reduce(
                    $links,
                    static function(Set $links, A $a): Set {
                        return $links->add($a->href());
                    }
                );
        } catch (ElementNotFound $e) {
            //pass
        }

        $links = $links->map(function(Url $link) use ($request, $attributes): Url {
            return ($this->resolve)(
                $request,
                $attributes,
                $link
            );
        });
        $links = (new RemoveDuplicatedUrls)($links);

        if ($links->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $links)
        );
    }

    public static function key(): string
    {
        return 'links';
    }
}
