<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Specification;

use Innmind\MediaType\MediaType;
use Innmind\Immutable\Set;

final class Html
{
    /** @var Set<string> */
    private static ?Set $allowed = null;

    public function __invoke(MediaType $type): bool
    {
        $type = (new MediaType(
            $type->topLevel(),
            $type->subType(),
            $type->suffix(),
        ))->toString();

        return self::allowed()->contains($type);
    }

    /**
     * @see https://www.w3.org/2003/01/xhtml-mimetype/
     *
     * @return Set<string>
     */
    private static function allowed(): Set
    {
        return self::$allowed ??= Set::strings(
            'text/html',
            'text/xml',
            'application/xml',
            'application/xhtml+xml',
        );
    }
}
