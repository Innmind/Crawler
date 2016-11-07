<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    UrlResolver,
    Visitor\RemoveDuplicatedUrls
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    ElementInterface,
    Visitor\Text
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Visitor\Body,
    Exception\ElementNotFoundException,
    Element\Img
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
    Map,
    Pair,
    Set
};

final class ImagesParser implements ParserInterface
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

        try {
            $body = (new Body)($document);
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $images = $this
            ->images($body)
            ->map(function(UrlInterface $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    $this->resolver->resolve($request, $attributes, $url),
                    $description
                );
            });
        $figures = $this
            ->figures($body)
            ->map(function(UrlInterface $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    $this->resolver->resolve($request, $attributes, $url),
                    $description
                );
            });

        $images = $this->removeDuplicates($images, $figures);

        if ($images->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $images,
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
        return 'images';
    }

    private function images(ElementInterface $body): Map
    {
        return (new Elements('img'))($body)
            ->filter(function(NodeInterface $img): bool {
                return $img instanceof Img;
            })
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(Map $images, Img $img): Map {
                    return $images->put(
                        $img->src(),
                        $img->attributes()->contains('alt') ?
                            $img->attributes()->get('alt')->value() : ''
                    );
                }
            );
    }

    private function figures(ElementInterface $body): Map
    {
        return (new Elements('figure'))($body)
            ->filter(function(NodeInterface $figure): bool {
                try {
                    $img = (new Element('img'))($figure);

                    return $img instanceof Img;
                } catch (ElementNotFoundException $e) {
                    return false;
                }
            })
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(Map $images, ElementInterface $figure): Map {
                    $img = (new Element('img'))($figure);

                    try {
                        $caption = (new Element('figcaption'))($figure);

                        return $images->put(
                            $img->src(),
                            (new Text)($caption)
                        );
                    } catch (ElementNotFoundException $e) {
                        return $images->put(
                            $img->src(),
                            $img->attributes()->contains('alt') ?
                                $img->attributes()->get('alt')->value() : ''
                        );
                    }
                }
            );
    }

    private function removeDuplicates(Map $images, Map $figures): Map
    {
        $urls = $figures->reduce(
            new Set(UrlInterface::class),
            function(Set $urls, UrlInterface $url): Set {
                return $urls->add($url);
            }
        );
        $urls = $images->reduce(
            $urls,
            function(Set $urls, UrlInterface $url): Set {
                return $urls->add($url);
            }
        );
        $urls = (new RemoveDuplicatedUrls)($urls);

        return $figures
            ->merge($images)
            ->filter(function(UrlInterface $url) use ($urls): bool {
                return $urls->contains($url);
            });
    }
}
