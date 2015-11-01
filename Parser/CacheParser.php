<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class CacheParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse(
        HttpResource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if ($response->hasHeader('Cache-Control')) {
            $directives = [];
            preg_match(
                '/s-maxage=(?P<cache>\d+)/',
                $response->getHeader('Cache-Control'),
                $directives
            );

            if (isset($directives['cache'])) {
                $cache = (new \DateTime)->modify(sprintf(
                    '+%s seconds',
                    (int) $directives['cache']
                ));
                $resource->set('expires_at', $cache);
            }
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'cache';
    }
}
