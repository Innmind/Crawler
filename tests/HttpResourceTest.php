<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream,
    File,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class HttpResourceTest extends TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            $url = Url::fromString('http://example.com/foo'),
            $mediaType = MediaType::fromString('application/json'),
            $attributes = new Map('string', Attribute::class),
            $content = new StringStream('')
        );

        $this->assertInstanceOf(File::class, $resource);
        $this->assertSame($url, $resource->url());
        $this->assertSame('foo', (string) $resource->name());
        $this->assertSame($mediaType, $resource->mediaType());
        $this->assertSame($attributes, $resource->attributes());
        $this->assertSame($content, $resource->content());
    }

    public function testThrowWhenInvalidAttributeMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type MapInterface<string, Innmind\Crawler\HttpResource\Attribute>');

        new HttpResource(
            Url::fromString('http://example.com/foo'),
            MediaType::fromString('application/json'),
            new Map('int', 'int'),
            new StringStream('')
        );
    }

    public function testBuildDefaultName()
    {
        $resource = new HttpResource(
            Url::fromString('http://example.com'),
            MediaType::fromString('application/json'),
            new Map('string', Attribute::class),
            new StringStream('')
        );

        $this->assertSame('index', (string) $resource->name());
    }
}
