# Crawler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/Innmind/Crawler/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Crawler/build-status/develop)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/833b6390-4f14-47f8-9362-5e90e6afde13/big.png)](https://insight.sensiolabs.com/projects/833b6390-4f14-47f8-9362-5e90e6afde13)

This tool allows you to extract a lot of usefu informations out of a web page (may it be html, an image, or anything else).

## Installation

```sh
composer require innmind/crawler
```

## Usage

```php
use Innmind\Crawler\Crawler;
use Innmind\Crawler\Request;
use Innmind\Crawler\Parser\UriParser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GuzzleHttp\Client;

$crawler = new Crawler(
    new Client,
    new UriParser,
    new EventDispatcher
);
$resource = $crawler->crawl(new Request('http://github.com'));
$resource->getUrl(); // http://github.com/
$resource->getContentType(); // text/html
$resource->get('scheme'); // http
$resource->get('host'); // github.com
// etc...
```

Here we use the `UriParser` to extract the info from the crawled page, but the `Crawler` accepts any object implementing the `ParserInterface`. And so you can easily use the [`Parser`](Parser.php) class to run multiple parsers at once.

The usage is simple:

```php
use Innmind\Crawler\Parser;
use Innmind\Crawler\Parser\UriParser;

$parser = new Parser;
$parser->addPass(new UriParser);
// you can add as many as you want
```

Take a look at the [parser folder](Parser/) to see all built-in parsers.

### Stopwatch

The crawler also use the symfony [`Stopwatch`](https://github.com/symfony/stopwatch) so you can have some infos about how long each parser took. You can retrieve the stopwatch as follows:

```php
$resource = $crawler->crawl(/*...*/);
$stopwatch = $crawler->getStopwatch($resource);
```

## Events

You can hook on 4 events:

* `Events::PRE_REQUEST` before the guzzle request is sent ([`PreRequestEvent`](Event/PreRequestEvent.php))
* `Events::POST_REQUEST` once we have the http response ([`PostRequestEvent`](Event/PostRequestEvent.php))
* `Events::PRE_PARSING` before the parser is called ([`ParsingEvent`](Event/ParsingEvent.php))
* `Events::POST_PARSING` once the parser has finished his job ([`ParsingEvent`](Event/ParsingEvent.php))

## Gotchas

If you do multiple crawls in one php process, you may want to call the method `release` on the crawler once a resource is crawled; otherwise the crawler will keep a reference to the resource and the associated stopwatch (and so can't be garbage collected).

Example:
```php
$resource = $crawler->crawl(/*...*/);
$crawler->release($resource);
```

In case you use [parsers](Parser/) with a dependency on the [`DomCrawlerFactory`](DomCrawlerFactory.php), you should call the `release` method on it so the symfony `Crawler` object crawler can be garbage collected (as it may be a huge object). You can hook at the `Events::POST_PARSING` to do so.

## Building your parser

Obviously you can build your own parser to enhance the whole crawling process. You only need to build a class implementing the [`ParserInterface`](ParserInterface.php) interface.

The `getName` method should return a unique name as this is used by the stopwatch inside the `Parser` to name the time sections.
