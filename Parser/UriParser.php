<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;
use Pdp\PublicSuffixListManager;
use Pdp\Parser;

class UriParser implements ParserInterface
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parser(
            (new PublicSuffixListManager)->getList()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        $url = $this->parser->parseUrl($resource->getUrl());

        $resource
            ->set('scheme', $url->scheme)
            ->set('host', (string) $url->host)
            ->set('domain', $url->host->registerableDomain)
            ->set('tld', $url->host->publicSuffix)
            ->set('port', $url->port)
            ->set('path', $url->path)
            ->set('query', $url->query)
            ->set('http_headers', $response->getHeaders());

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'uri';
    }
}
