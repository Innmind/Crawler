<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Immutable\MapInterface;

interface Attributes extends Attribute, \Iterator
{
    /**
     * @return MapInterface<string, Attribute>
     */
    public function content(): MapInterface;
}
