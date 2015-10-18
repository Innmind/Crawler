<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class CharsetParser implements ParserInterface
{
    protected $crawlerFactory;

    public function __construct(DomCrawlerFactory $crawlerFactory)
    {
        $this->crawlerFactory = $crawlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        $pattern = '/charset="?(?P<charset>[a-zA-Z\-\d]+)"?/';

        if (preg_match($pattern, $resource->getContentType(), $matches)) {
            $resource->set('charset', $matches['charset']);

            return $resource;
        }

        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $charset = $dom->filter('meta[charset]');

        if ($charset->count() === 1) {
            $resource->set('charset', $charset->attr('charset'));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'charset';
    }
}
