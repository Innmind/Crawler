<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\Reader;
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

final class JournalParser implements Parser
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
            $articles = (new Elements('article'))(
                Element::body()($document),
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($articles->size() <= 1) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), true),
        );
    }

    public static function key(): string
    {
        return 'journal';
    }
}
