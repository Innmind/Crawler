<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    Exception\DomainException,
    Exception\InvalidOpenGraphAttribute,
    HttpResource\Attribute\Attribute,
    Parser\Html\HtmlTrait,
};
use Innmind\Xml\{
    Reader,
    Element,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
    Str,
};

abstract class AbstractPropertyParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $property;

    public function __construct(
        Reader $read,
        string $property
    ) {
        if (Str::of($property)->empty()) {
            throw new DomainException;
        }

        $this->read = $read;
        $this->property = 'og:'.$property;
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

        try {
            $values = (new Elements('meta'))(
                (new Head)($document)
            )
                ->filter(static function(Element $meta): bool {
                    return $meta->attributes()->contains('property') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(Element $meta): bool {
                    return $meta->attributes()->get('property')->value() === $this->property;
                })
                ->reduce(
                    new Set('string'),
                    static function(SetInterface $values, Element $meta): SetInterface {
                        return $values->add(
                            $meta->attributes()->get('content')->value()
                        );
                    }
                );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($values->size() === 0) {
            return $attributes;
        }

        try {
            return $attributes->put(
                static::key(),
                new Attribute(static::key(), $this->parseValues($values))
            );
        } catch (InvalidOpenGraphAttribute $e) {
            return $attributes;
        }
    }

    /**
     * @param SetInterface<string> $values
     *
     * @return mixed
     */
    abstract protected function parseValues(SetInterface $values);
}
