<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Http\ContentTypeParser,
    Specification\Html,
};
use Innmind\Immutable\MapInterface;

trait HtmlTrait
{
    private function isHtml(MapInterface $attributes): bool
    {
        return $attributes->contains(ContentTypeParser::key()) &&
            (new Html)->isSatisfiedBy(
                $attributes->get(ContentTypeParser::key())->content()
            );
    }
}
