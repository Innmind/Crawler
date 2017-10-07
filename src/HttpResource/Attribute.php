<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

interface Attribute
{
    public function name(): string;
    public function content();
}
