<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Specification;

use Innmind\Crawler\Specification\Image;
use Innmind\Filesystem\MediaType\MediaType;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testIsSatisfiedBy(bool $expected, string $type)
    {
        $spec = new Image;

        $this->assertSame(
            $expected,
            $spec->isSatisfiedBy(MediaType::fromString($type))
        );
    }

    public function cases(): array
    {
        return [
            [true, 'image/jpg'],
            [true, 'image/jpeg'],
            [true, 'image/png'],
            [true, 'image/png+whatever'],
            [true, 'image/vnd.webp+whatever'],
            [false, 'application/pdf'],
        ];
    }
}
