<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Visitor\Head,
    Exception\ElementNotFoundException
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    Visitor\Text
};
use Innmind\Immutable\MapInterface;

final class TitleParser implements Parser
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

        $title = $this->getH1($document);

        if (empty($title)) {
            $title = $this->getTitle($document);

            if (empty($title)) {
                return $attributes;
            }
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $title)
        );
    }

    public static function key(): string
    {
        return 'title';
    }

    private function getH1(NodeInterface $document): string
    {
        $h1s = (new Elements('h1'))($document);

        if ($h1s->size() !== 1) {
            return '';
        }

        return trim((new Text)($h1s->current()));
    }

    private function getTitle(NodeInterface $document): string
    {
        try {
            $title = (new Text)(
                (new Element('title'))(
                    (new Head)($document)
                )
            );

            return trim($title);
        } catch (ElementNotFoundException $e) {
            return '';
        }
    }
}
