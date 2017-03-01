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
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Colour\{
    Colour,
    Exception\ExceptionInterface
};
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class ThemeColorParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $clock;

    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        $this->reader = $reader;
        $this->clock = $clock;
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
            $meta = (new Elements('meta'))(
                (new Head)($document)
            )
                ->filter(function(ElementInterface $meta): bool {
                    return $meta->attributes()->contains('name') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(ElementInterface $meta): bool {
                    $name = $meta
                        ->attributes()
                        ->get('name')
                        ->value();

                    return (string) (new Str($name))->toLower() === 'theme-color';
                });
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        if ($meta->size() !== 1) {
            return $attributes;
        }

        try {
            $colour = Colour::fromString(
                $meta
                    ->current()
                    ->attributes()
                    ->get('content')
                    ->value()
            );
        } catch (ExceptionInterface $e) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $colour,
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
        return 'theme-color';
    }
}
