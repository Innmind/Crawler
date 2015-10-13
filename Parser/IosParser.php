<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class IosParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = new Crawler((string) $response->getBody());
        $meta = $dom->filter('meta[name="apple-itunes-app"][content*="app-argument="]');

        if ($meta->count() === 1) {
            preg_match(
                '/app\-argument\="?(?P<uri>.*)"?$/',
                $meta->attr('content'),
                $matches
            );

            if (isset($matches['uri'])) {
                $resource->set('ios', $matches['uri']);
            }
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'ios';
    }
}
