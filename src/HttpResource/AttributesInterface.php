<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Immutable\MapInterface;

interface AttributesInterface extends AttributeInterface, \Iterator
{
    /**
     * @return MapInterface<string, AttributeInterface>
     */
    public function content(): MapInterface;
}
