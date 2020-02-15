<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Exception\ElementNotFound,
};
use Innmind\Xml\{
    Reader,
    Node,
    Visitor\Text,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\first;

final class TitleParser implements Parser
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

        $title = $this->getH1($document);

        if (Str::of($title)->empty()) {
            $title = $this->getTitle($document);

            if (Str::of($title)->empty()) {
                return $attributes;
            }
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $title),
        );
    }

    public static function key(): string
    {
        return 'title';
    }

    private function getH1(Node $document): string
    {
        $h1s = (new Elements('h1'))($document);

        if ($h1s->size() !== 1) {
            return '';
        }

        return Str::of((new Text)(first($h1s)))->trim()->toString();
    }

    private function getTitle(Node $document): string
    {
        try {
            $title = (new Text)(
                (new Element('title'))(
                    Element::head()($document),
                ),
            );

            return Str::of($title)->trim()->toString();
        } catch (ElementNotFound $e) {
            return '';
        }
    }
}
