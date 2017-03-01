<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    ElementInterface
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFoundException
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\MapInterface;

final class CharsetParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
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
            $metas = (new Elements('meta'))(
                (new Head)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $meta = $metas->filter(function(ElementInterface $meta): bool {
            return $meta->attributes()->contains('charset');
        });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $meta
                    ->current()
                    ->attributes()
                    ->get('charset')
                    ->value()
            )
        );
    }

    public static function key(): string
    {
        return 'charset';
    }
}
