# Crawler

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Crawler/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/build-status/develop) |

This tool allows you to extract a lot of useful informations out of a web page (may it be html, an image, or anything else).

## Installation

```sh
composer require innmind/crawler
```

## Usage

```php
use Innmind\Crawler\{
    Crawler,
    Parser\Http\ContentTypeParser
};
use Innmind\Http\{
    Translator\Response\Psr7Translator,
    Factory\Header\Factories,
    Factory\HeaderFactoryInterface,
    Message\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface
};
use Innmind\HttpTransport\GuzzleTransport;
use Innmind\Url\Url;
use Innmind\Filesystem\Stream\NullStream;
use Innmind\Immutable\Map;
use GuzzleHttp\Client as Http;

$crawler = new Crawler(
    new GuzzleTransport(
        new Http,
        new Psr7Translator(
            Factories::default()
        )
    ),
    new ContentTypeParser
);

$resource = $crawler->execute(
    new Request(
        Url::fromString('https://en.wikipedia.org/wiki/H2g2'),
        new Method('GET'),
        new ProtocolVersion(2, 0),
        new Headers(
            new Map('string', HeaderInterface::class)
        ),
        new NullStream
    )
);
```

Here `$resource` is an instance of [`HttpResource`](src/HttpResource.php), with a single attribute available we only used the `ContentTypeParser` but there's [plenty more](src/Parser).
