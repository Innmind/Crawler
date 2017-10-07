<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
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
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;

final class AndroidParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
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
                return (string) $link->href()->scheme() === 'android-app';
            });

        if ($link->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $link->current()->href())
        );
    }

    public static function key(): string
    {
        return 'android';
    }
}
