<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    UrlResolver
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Visitor\Body,
    Exception\ElementNotFoundException,
    Element\Link,
    Element\A
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Immutable\{
    MapInterface,
    Set
};

final class LinksParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $clock;
    private $resolver;

    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock,
        UrlResolver $resolver
    ) {
        $this->reader = $reader;
        $this->clock = $clock;
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());
        $links = new Set(UrlInterface::class);

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            )
                ->filter(function(NodeInterface $link): bool {
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
                    function(Set $links, Link $link): Set {
                        return $links->add($link->href());
                    }
                );
        } catch (ElementNotFoundException $e) {
            //pass
        }

        try {
            $links = (new Elements('a'))(
                (new Body)($document)
            )
                ->filter(function(NodeInterface $a): bool {
                    return $a instanceof A;
                })
                ->filter(function(A $a): bool {
                    return substr((string) $a, 0, 2) !== '/#';
                })
                ->reduce(
                    $links,
                    function(Set $links, A $a): Set {
                        return $links->add($a->href());
                    }
                );
        } catch (ElementNotFoundException $e) {
            //pass
        }

        $links = $links
            ->map(function(UrlInterface $link) use ($request, $attributes): UrlInterface {
                return $this->resolver->resolve(
                    $request,
                    $attributes,
                    $link
                );
            })
            ->reduce(
                new Set('string'),
                function(Set $links, UrlInterface $link): Set {
                    return $links->add((string) $link);
                }
            )
            ->reduce(
                new Set(UrlInterface::class),
                function(Set $links, string $link): Set {
                    return $links->add(Url::fromString($link));
                }
            );

        if ($links->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $links,
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }

    public static function key(): string
    {
        return 'links';
    }
}
