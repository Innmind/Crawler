<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser\Http\ContentTypeParser,
    Specification\Image
};
use Innmind\Immutable\MapInterface;

trait ImageTrait
{
    private function isImage(MapInterface $attributes): bool
    {
        return $attributes->contains(ContentTypeParser::key()) &&
            (new Image)->isSatisfiedBy(
                $attributes->get(ContentTypeParser::key())->content()
            );
    }
}
