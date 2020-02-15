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
    Visitor\Element,
    Exception\ElementNotFound,
    Element\Link,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\first;

final class ContentParser implements Parser
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
        $document = (new RemoveElements('script', 'style'))($document);
        $document = (new RemoveComments)($document);

        try {
            $body = Element::body()($document);
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
            $node = first($elements);
        } else {
            foreach (['main', 'article'] as $tag) {
                $elements = (new Elements($tag))($body);

                if ($elements->size() === 1) {
                    break;
                }
            }

            if ($elements->size() === 1) {
                $node = first($elements);
            } else {
                /** @var Sequence<Node> */
                $nodes = Sequence::of(Node::class, $body);
                $node = (new FindContentNode)($nodes);
            }
        }

        $text = Str::of((new Text)($node));

        if ($text->empty()) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $text->toString()),
        );
    }

    public static function key(): string
    {
        return 'content';
    }
}
