<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser,
    Parser\Http\ContentTypeParser,
    Specification\Image,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;

final class ImageParser implements Parser
{
    private Parser $parse;
    private Image $image;

    public function __construct(Parser $parse)
    {
        $this->parse = $parse;
        $this->image = new Image;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (!$attributes->contains(ContentTypeParser::key())) {
            return $attributes;
        }

        /** @var MediaType */
        $type = $attributes->get(ContentTypeParser::key())->content();

        if (!($this->image)($type)) {
            return $attributes;
        }

        return ($this->parse)($request, $response, $attributes);
    }

    public static function key(): string
    {
        return 'image';
    }
}
