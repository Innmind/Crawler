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
use function Innmind\Crawler\bootstrap;
use Innmind\OperatingSystem\Factory;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
};

$os = Factory::build();

$crawl = bootstrap(
    $os->remote()->http(),
    $os->clock(),
    reader(),
    new UrlResolver
);

$resource = $crawl(
    new Request(
        Url::fromString('https://en.wikipedia.org/wiki/H2g2'),
        new Method('GET'),
        new ProtocolVersion(2, 0)
    )
);
```

Here `$resource` is an instance of [`HttpResource`](src/HttpResource.php).

