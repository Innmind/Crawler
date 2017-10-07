<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Xml\ReaderInterface;
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFoundException
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;

final class JournalParser implements ParserInterface
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
            $articles = (new Elements('article'))(
                (new Body)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        if ($articles->size() <= 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), true)
        );
    }

    public static function key(): string
    {
        return 'journal';
    }
}
