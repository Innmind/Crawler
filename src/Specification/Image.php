<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Specification;

use Innmind\Filesystem\MediaTypeInterface;

final class Image
{
    public function isSatisfiedBy(MediaTypeInterface $type): bool
    {
        return $type->topLevel() === 'image';
    }
}
