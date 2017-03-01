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
    Exception\ElementNotFoundException,
    Element\Link
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\MapInterface;

final class RssParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $resolver;

    public function __construct(ReaderInterface $reader, UrlResolver $resolver)
    {
        $this->reader = $reader;
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            )
                ->filter(function(NodeInterface $link): bool {
                    return $link instanceof Link;
                })
                ->filter(function(Link $link): bool {
                    return $link->relationship() === 'alternate' &&
                        $link->attributes()->contains('type') &&
                        $link->attributes()->get('type')->value() === 'application/rss+xml';
                });
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        if ($links->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $this->resolver->resolve(
                    $request,
                    $attributes,
                    $links->current()->href()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'rss';
    }
}
