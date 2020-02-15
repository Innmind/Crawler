# Crawler

[![Build Status](https://github.com/Innmind/Crawler/workflows/CI/badge.svg)](https://github.com/Innmind/Crawler/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/Crawler/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Crawler)
[![Type Coverage](https://shepherd.dev/github/Innmind/Crawler/coverage.svg)](https://shepherd.dev/github/Innmind/Crawler)

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
    ProtocolVersion,
};
use function Innmind\Html\bootstrap as reader;

$os = Factory::build();

$crawl = bootstrap(
    $os->remote()->http(),
    $os->clock(),
    reader(),
    new UrlResolver
);

$resource = $crawl(
    new Request(
        Url::of('https://en.wikipedia.org/wiki/H2g2'),
        new Method('GET'),
        new ProtocolVersion(2, 0),
    ),
);
```

Here `$resource` is an instance of [`HttpResource`](src/HttpResource.php).

