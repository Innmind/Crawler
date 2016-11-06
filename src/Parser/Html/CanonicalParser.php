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

final class CanonicalParser implements ParserInterface
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
            $links = (new Elements('link'))(
                (new Head)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $link = $links
            ->filter(function(NodeInterface $link): bool {
                return $link instanceof Link;
            })
            ->filter(function(Link $link): bool {
                return $link->relationship() === 'canonical';
            });

        if ($link->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $this->resolver->resolve(
                    $request,
                    $attributes,
                    $link->current()->href()
                ),
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
        return 'canonical';
    }
}
