<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute,
};
use Innmind\Xml\Reader;
use Innmind\Html\{
    Visitor\Element,
    Exception\ElementNotFound,
    Element\Base,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\Url;
use Innmind\Immutable\Map;

final class BaseParser implements Parser
{
    private Reader $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $base = (new Element('base'))(
                Element::head()($document),
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if (!$base instanceof Base) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute\Attribute(self::key(), $base->href()),
        );
    }

    public static function key(): string
    {
        return 'base';
    }

    /**
     * @param Map<string, Attribute> $attributes
     */
    public static function find(Map $attributes, Url $default): Url
    {
        if (!$attributes->contains(self::key())) {
            return $default;
        }

        /** @var mixed */
        $base = $attributes->get(self::key())->content();

        if (!$base instanceof Url) { // in case somebody overwrote the attribute
            return $default;
        }

        return $base;
    }
}
