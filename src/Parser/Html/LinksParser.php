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
    Visitor\Head,
    Visitor\Body,
    Exception\ElementNotFound,
    Element\Link,
    Element\A,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class LinksParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $resolver;

    public function __construct(Reader $read, UrlResolver $resolver)
    {
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
        $links = new Set(UrlInterface::class);

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            )
                ->filter(function(Node $link): bool {
                    return $link instanceof Link;
                })
                ->filter(function(Link $link): bool {
                    return in_array(
                        $link->relationship(),
                        ['first', 'next', 'previous', 'last'],
                        true
                    );
                })
                ->reduce(
                    $links,
                    function(SetInterface $links, Link $link): SetInterface {
                        return $links->add($link->href());
                    }
                );
        } catch (ElementNotFound $e) {
            //pass
        }

        try {
            $links = (new Elements('a'))(
                (new Body)($document)
            )
                ->filter(function(Node $a): bool {
                    return $a instanceof A;
                })
                ->filter(function(A $a): bool {
                    return substr((string) $a, 0, 1) !== '#';
                })
                ->reduce(
                    $links,
                    function(SetInterface $links, A $a): SetInterface {
                        return $links->add($a->href());
                    }
                );
        } catch (ElementNotFound $e) {
            //pass
        }

        $links = $links->map(function(UrlInterface $link) use ($request, $attributes): UrlInterface {
            return $this->resolver->resolve(
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
