<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Specification;

use Innmind\Filesystem\MediaType;
use Innmind\Immutable\Set;

final class Html
{
    private static Set $allowed;

    public function isSatisfiedBy(MediaType $type): bool
    {
        $type = (string) new MediaType\MediaType(
            $type->topLevel(),
            $type->subType(),
            $type->suffix(),
            $type->parameters()->clear()
        );

        return self::allowed()->contains($type);
    }

    /**
     * @see https://www.w3.org/2003/01/xhtml-mimetype/
     */
    private static function allowed(): Set
    {
        return self::$allowed ?? self::$allowed = Set::of(
            'string',
            'text/html',
            'text/xml',
            'application/xml',
            'application/xhtml+xml'
        );
    }
}
