<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Immutable\Map;

interface Attributes extends Attribute
{
    /**
     * @return Map<string, Attribute>
     */
    public function content(): Map;
}
