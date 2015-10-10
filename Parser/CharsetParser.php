<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class CharsetParser implements ParserInterface
{
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

        $dom = new Crawler((string) $response->getBody());
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
