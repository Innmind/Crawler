<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\Reader;
use Innmind\Html\{
    Visitor\Element,
    Visitor\Head,
    Exception\ElementNotFound,
    Element\Base,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class BaseParser implements Parser
{
    use HtmlTrait;

    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        try {
            $base = (new Element('base'))(
                (new Head)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if (!$base instanceof Base) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $base->href())
        );
    }

    public static function key(): string
    {
        return 'base';
    }
}
