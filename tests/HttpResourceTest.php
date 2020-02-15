<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable\Stream;
use Innmind\Filesystem\File;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class HttpResourceTest extends TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            $url = Url::of('http://example.com/foo'),
            $mediaType = MediaType::of('application/json'),
            $attributes = Map::of('string', Attribute::class),
            $content = Stream::ofContent('')
        );

        $this->assertInstanceOf(File::class, $resource);
        $this->assertSame($url, $resource->url());
        $this->assertSame('foo', $resource->name()->toString());
        $this->assertSame($mediaType, $resource->mediaType());
        $this->assertSame($attributes, $resource->attributes());
        $this->assertSame($content, $resource->content());
    }

    public function testThrowWhenInvalidAttributeMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Map<string, Innmind\Crawler\HttpResource\Attribute>');

        new HttpResource(
            Url::of('http://example.com/foo'),
            MediaType::of('application/json'),
            Map::of('int', 'int'),
            Stream::ofContent('')
        );
    }

    public function testBuildDefaultName()
    {
        $resource = new HttpResource(
            Url::of('http://example.com'),
            MediaType::of('application/json'),
            Map::of('string', Attribute::class),
            Stream::ofContent('')
        );

        $this->assertSame('index', $resource->name()->toString());
    }
}
