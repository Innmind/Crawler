<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    ParserInterface,
    Exception\InvalidArgumentException,
    HttpResource\Attribute,
    Parser\Html\HtmlTrait
};
use Innmind\Xml\{
    ReaderInterface,
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
use Innmind\Immutable\{
    MapInterface,
    Set,
    SetInterface
};

abstract class AbstractPropertyParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $property;

    public function __construct(
        ReaderInterface $reader,
        string $property
    ) {
        if (empty($property)) {
            throw new InvalidArgumentException;
        }

        $this->reader = $reader;
        $this->property = 'og:'.$property;
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
            $values = (new Elements('meta'))(
                (new Head)($document)
            )
                ->filter(function(ElementInterface $meta): bool {
                    return $meta->attributes()->contains('property') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(ElementInterface $meta): bool {
                    return $meta->attributes()->get('property')->value() === $this->property;
                })
                ->reduce(
                    new Set('string'),
                    function(Set $values, ElementInterface $meta): Set {
                        return $values->add(
                            $meta->attributes()->get('content')->value()
                        );
                    }
                );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        if ($values->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            static::key(),
            new Attribute(static::key(), $this->parseValues($values))
        );
    }

    /**
     * @param SetInterface<string> $values
     *
     * @return mixed
     */
    abstract protected function parseValues(SetInterface $values);
}
