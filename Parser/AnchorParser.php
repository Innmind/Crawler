<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class AnchorParser implements ParserInterface
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
        $anchors = $dom
            ->filter('a[href^="#"]')
            ->reduce(function (Crawler $node) {
                return (bool) preg_match('/^#.+$/', $node->attr('href'));
            })
            ->each(function (Crawler $node) {
                return substr($node->attr('href'), 1);
            });
        $resource->set('anchors', array_unique($anchors));


        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'anchor';
    }
}
