<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource,
    HttpResource\AttributeInterface
};
use Innmind\Url\Url;
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream,
    FileInterface
};
use Innmind\Immutable\Map;

class HttpResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            $url = Url::fromString('http://example.com/foo'),
            $mediaType = MediaType::fromString('application/json'),
            $attributes = new Map('string', AttributeInterface::class),
            $content = new StringStream('')
        );

        $this->assertInstanceOf(FileInterface::class, $resource);
        $this->assertSame($url, $resource->url());
        $this->assertSame('foo', (string) $resource->name());
        $this->assertSame($mediaType, $resource->mediaType());
        $this->assertSame($attributes, $resource->attributes());
        $this->assertSame($content, $resource->content());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidAttributeMap()
    {
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
            new Map('string', AttributeInterface::class),
            new StringStream('')
        );

        $this->assertSame('index', (string) $resource->name());
    }
}
