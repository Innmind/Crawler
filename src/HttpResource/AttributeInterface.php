<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

interface AttributeInterface
{
    public function name(): string;
    public function content();

    /**
     * The time in milliseconds it took to parse this attribute
     *
     * @return int
     */
    public function parsingTime(): int;
}
