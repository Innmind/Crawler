<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\Math\Quantile\Quantile;
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
        $crawler = new Crawler;
        $crawler->addNode($dom);
        $stopwatch->stop('html_cleaning');

        $body = $crawler->filter('body');
        $roleArticle = $body->filter('[role="article"]');
        $roleDocument = $body->filter('[role="document"]');
        $roleMain = $body->filter('[role="main"]');
        $main = $body->filter('main');
        $article = $body->filter('article');

        switch (true) {
            case $roleArticle->count() === 1:
                $node = $roleArticle;
                break;
            case $roleDocument->count() === 1:
                $node = $roleDocument;
                break;
            case $roleMain->count() === 1:
                $node = $roleMain;
                break;
            case $main->count() === 1:
                $node = $main;
                break;
            case $article->count() === 1:
                $node = $article;
                break;
            case $body->count() === 1:
                $body = $body->eq(0);
                $stopwatch->start('content_finding');
                $node = $this->findContentNode($body);
                $stopwatch->stop('content_finding');
                break;
        }

        if (isset($node)) {
            $resource->set('content', trim($node->text()));
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

            if ($group->count() === 0) {
                return $node;
            }
        } else {
            $group = $node;
        }

        if ($group->count() === 1) {
            return $this->findContentNode($group);
        }

        $group->each(function(Crawler $child, $i) use (&$dispersion) {
            $dispersion[$i] = str_word_count($child->text());
        });

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

        $min = $quantile->quartile(min($lookup))->value();

        $nodes = $group->reduce(function(Crawler $child, $i) use ($min, $dispersion) {
            return $dispersion[$i] >= $min;
        });

        return $this->findContentNode($nodes);
    }
}
