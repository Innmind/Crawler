<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\Math\Quantile\Quantile;
use Innmind\Math\Regression\LinearRegression;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class ContentParser implements ParserInterface
{
    protected $crawlerFactory;
    protected $toIgnore = ['script', 'style'];

    public function __construct(DomCrawlerFactory $crawlerFactory)
    {
        $this->crawlerFactory = $crawlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        HttpResource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $stopwatch->start('html_cleaning');
        $crawler = $this->crawlerFactory->make($response);
        $dom = $crawler->getNode(0);
        $this->clean($dom);
        $this->clean($dom);
        $this->clean($dom);
        $crawler = new Crawler;
        $crawler->addNode($dom);
        $stopwatch->stop('html_cleaning');

        $body = $crawler->filter('body');

        if ($body->count() === 1) {
            $body = $body->eq(0);
            $stopwatch->start('content_finding');
            $node = $this->findContentNode($body);
            $stopwatch->stop('content_finding');
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'content';
    }

    /**
     * Remove all the script and stylesheet tags
     *
     * @param DOMNode $node
     *
     * @return void
     */
    protected function clean(\DOMNode $node)
    {
        if ($node->hasChildNodes()) {
            $length = $node->childNodes->length;

            for ($i = 0; $i < $length; $i++) {
                $child = $node->childNodes->item($i);

                if (!$child instanceof \DOMNode) {
                    continue;
                }

                if (in_array($child->nodeName, $this->toIgnore, true)) {
                    $node->removeChild($child);
                    $i--;
                    $length--;
                } else {
                    $this->clean($child);
                }
            }
        }
    }

    /**
     * Try to find the node where all the page content is set
     *
     * @param Crawler $node
     *
     * @return Crawler
     */
    protected function findContentNode(Crawler $node)
    {
        $dispersion = [];

        if ($node->count() === 1) {
            $group = $node->children();
        } else {
            $group = $node;
        }

        if ($group->count() === 1) {
            return $this->findContentNode($group);
        }

        $group->each(function(Crawler $child, $i) use (&$dispersion) {
            $dispersion[$i] = str_word_count($child->text());
        });

        if (count($dispersion) > 2) {
            $regression = new LinearRegression($dispersion);

            if ($regression->intercept() > 0) {
                foreach ($dispersion as $i => &$value) {
                    $value = $regression($i);
                }
            }
        }

        $quantile = new Quantile($dispersion);
        $lookup = [];

        for ($i = 1; $i < 5; $i++) {
            $diff = $quantile->quartile($i)->value() - $quantile->quartile($i - 1)->value();

            if ($diff >= $quantile->mean()) {
                $lookup[] = $i;
            }
        }

        if (empty($lookup)) {
            return $node;
        }

        $min = $quantile->quartile(min($lookup) - 1)->value();

        $nodes = $group->reduce(function(Crawler $child, $i) use ($min, $dispersion) {
            return $dispersion[$i] > $min;
        });

        return $this->findContentNode($nodes);
    }
}
