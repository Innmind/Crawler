<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Specification;

use Innmind\Filesystem\MediaType;
use Innmind\Immutable\Set;

final class Html
{
    public static $allowed;

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
        if (!self::$allowed) {
            self::$allowed = (new Set('string'))
                ->add('text/html')
                ->add('text/xml')
                ->add('application/xml')
                ->add('application/xhtml+xml');
        }

        return self::$allowed;
    }
}
