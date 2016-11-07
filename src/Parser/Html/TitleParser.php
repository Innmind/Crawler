<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
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

final class TitleParser implements ParserInterface
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

        $title = $this->getH1($document);

        if (empty($title)) {
            $title = $this->getTitle($document);

            if (empty($title)) {
                return $attributes;
            }
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $title,
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
