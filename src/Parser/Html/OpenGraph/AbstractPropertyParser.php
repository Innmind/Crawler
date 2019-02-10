<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    Exception\DomainException,
    Exception\InvalidOpenGraphAttribute,
    HttpResource\Attribute\Attribute,
    Parser\Html\HtmlTrait,
    Visitor\Html\OpenGraph,
};
use Innmind\Xml\Reader;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Str,
};

abstract class AbstractPropertyParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $extract;

    public function __construct(
        Reader $read,
        string $property
    ) {
        if (Str::of($property)->empty()) {
            throw new DomainException;
        }

        $this->read = $read;
        $this->extract = new OpenGraph($property);
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

        $values = ($this->extract)($document);

        if ($values->empty()) {
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
