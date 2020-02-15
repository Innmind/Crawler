<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Specification;

use Innmind\MediaType\MediaType;

final class Image
{
    public function __invoke(MediaType $type): bool
    {
        return $type->topLevel() === 'image';
    }
}
