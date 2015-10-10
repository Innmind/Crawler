<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class ImagesParser implements ParserInterface
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
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = new Crawler((string) $response->getBody());
        $images = [];
        $dom
            ->filter('figure')
            ->each(function(Crawler $node) use (&$images, $resource) {
                $img = $node->filter('img');
                $caption = $node->filter('figcaption');

                if ($img->count() !== 1) {
                    return;
                }

                $alt = $caption->count() === 1 ?
                    $caption->text() :
                    $img->attr('alt');

                $url = $this->resolver->resolve(
                    $resource->has('base') ? $resource->get('base') : $resource->getUrl(),
                    $img->attr('src')
                );
                $images[md5($url)] = [$url, $alt];
            });
        $dom
            ->filter('img')
            ->each(function(Crawler $node) use (&$images, $resource) {
                $url = $this->resolver->resolve(
                    $resource->has('base') ? $resource->get('base') : $resource->getUrl(),
                    $node->attr('src')
                );
                $key = md5($url);

                if (isset($images[$key])) {
                    return;
                }

                $images[$key] = [$url, $node->attr('alt')];
            });

        if (!empty($images)) {
            $resource->set('images', array_values($images));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'images';
    }
}
