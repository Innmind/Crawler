<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    Visitor\Html\RemoveElements,
    Visitor\Html\RemoveComments,
    Visitor\Html\FindContentNode,
    Visitor\Html\Role,
};
use Innmind\Xml\{
    Reader,
    Node,
    Visitor\Text,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFound,
    Element\Link,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class ContentParser implements Parser
{
    use HtmlTrait;

    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
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
        $document = (new RemoveElements('script', 'style'))($document);
        $document = (new RemoveComments)($document);

        try {
            $body = (new Body)($document);
        } catch (ElementNotFound $e) {
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
                    Map::of('int', Node::class)
                        (0, $body)
                );
            }
        }

        $text = Str::of((new Text)($node));

        if ($text->empty()) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), (string) $text)
        );
    }

    public static function key(): string
    {
        return 'content';
    }
}
