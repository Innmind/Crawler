<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class TitleParser implements ParserInterface
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
        $h1 = $dom->filter('h1');
        $title = $dom->filter('head title');

        if ($h1->count() === 1 && trim($h1->text()) !== '') {
            $resource->set('title', trim($h1->text()));
        } else if ($title->count() === 1 && trim($title->text()) !== '') {
            $resource->set('title', trim($title->text()));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'title';
    }
}
