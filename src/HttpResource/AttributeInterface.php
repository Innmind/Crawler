<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

interface AttributeInterface
{
    public function name(): string;
    public function content();
}
