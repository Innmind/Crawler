<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

interface Attribute
{
    public function name(): string;

    /**
     * @return mixed
     */
    public function content();
}
