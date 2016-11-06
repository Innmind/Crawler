<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    Visitor\Html\RemoveNodes,
    Visitor\Html\FindContentNode,
    Visitor\Html\Role
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    Visitor\Text
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFoundException,
    Element\Link
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    Map
};

final class ContentParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $clock;
    private $toIgnore;

    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        $this->reader = $reader;
        $this->clock = $clock;
        $this->toIgnore = (new Set('string'))
            ->add('script')
            ->add('style');
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
        $document = (new RemoveNodes($this->toIgnore))($document);

        try {
            $body = (new Body)($document);
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        foreach (['article', 'document', 'main'] as $role) {
            $elements = (new Role($role))($body);

            if ($elements->size() === 1) {
                break;
            }
        }

        if ($elements->size() === 1) {
            $node = $elements->current();
        } else {
            foreach (['main', 'article'] as $tag) {
                $elements = (new Elements($tag))($body);

                if ($elements->size() === 1) {
                    break;
                }
            }

            if ($elements->size() === 1) {
                $node = $elements->current();
            } else {
                $node = (new FindContentNode)(
                    (new Map('int', NodeInterface::class))
                        ->put(0, $body)
                );
            }
        }

        $text = trim((new Text)($node));

        if (empty($text)) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $text,
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
        return 'content';
    }
}
