<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Specification;

use Innmind\Crawler\Specification\Html;
use Innmind\Filesystem\MediaType\MediaType;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider cases
     */
    public function testIsSatisfiedBy(bool $expected, string $type)
    {
        $spec = new Html;

        $this->assertSame(
            $expected,
            $spec->isSatisfiedBy(MediaType::fromString($type))
        );
    }

    public function cases(): array
    {
        return [
            [true, 'text/html'],
            [true, 'text/xml'],
            [true, 'application/xhtml+xml'],
            [true, 'application/xml'],
            [false, 'application/vnd.sealedmedia.softseal-html'],
            [false, 'application/vnd.pwg-xhtml-print+xml'],
            [false, 'application/vnd.oipf.dae.xhtml+xml'],
            [false, 'application/vnd.ms-htmlhelp'],
            [false, 'application/vnd.dtg.local-html'],
        ];
    }
}
