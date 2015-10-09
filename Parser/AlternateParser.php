<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class AlternateParser implements ParserInterface
{
    protected $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        $stopwatch->start('http_link');
        $http = $this->parseHttpLinks($resource, $response);
        $stopwatch->stop('http_link');

        $stopwatch->start('html_link');
        $html = $this->parseHtmlLinks($resource, $response);
        $stopwatch->stop('html_link');

        $links = array_merge($http, $html);
        $alternates = [];

        foreach ($links as $link) {
            if (!isset($alternates[$link[0]])) {
                $alternates[$link[0]] = [];
            }

            $alternates[$link[0]][] = $link[1];
        }

        if (!empty($alternates)) {
            $resource->set('alternates', $alternates);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'alternate';
    }

    /**
     * Try to find alternate version of the resource via http links
     *
     * @param Resource $resource
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function parseHttpLinks(
        Resource $resource,
        ResponseInterface $response
    ) {
        $links = $response::parseHeader($response, 'Link');
        $alternates = [];

        foreach ($links as $link) {
            if (
                !isset($link['rel']) ||
                $link['rel'] !== 'alternate' ||
                !isset($link['hreflang'])
            ) {
                continue;
            }

            $alternates[] = [
                $link['hreflang'],
                $this->resolver->resolve(
                    $resource->getUrl(),
                    substr($link[0], 1, -1)
                ),
            ];
        }

        return $alternates;
    }

    /**
     * Try to find alternate version of the resource in the html content
     *
     * @param Resource $resource
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function parseHtmlLinks(
        Resource $resource,
        ResponseInterface $response
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return [];
        }

        $dom = new Crawler((string) $response->getBody());
        $alternates = $dom->filter('link[rel="alternate"][href][hreflang]');

        if ($alternates->count() === 0) {
            return [];
        }

        return $alternates->each(function (Crawler $node) use ($resource) {
            return [
                $node->attr('hreflang'),
                $this->resolver->resolve(
                    $resource->getUrl(),
                    $node->attr('href')
                ),
            ];
        });
    }
}
