<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Exception\ContentTooDispersed;
use Innmind\Xml\{
    Node,
    Visitor\Text,
};
use Innmind\Math\{
    Quantile\Quantile,
    Regression\Dataset,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class FindContentNode
{
    /**
     * @param MapInterface<int, Node> $nodes
     */
    public function __invoke(MapInterface $nodes): Node
    {
        if (
            (string) $nodes->keyType() !== 'int' ||
            (string) $nodes->valueType() !== Node::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<int, %s>',
                Node::class
            ));
        }

        $nodes->rewind();

        if ($nodes->size() === 1) {
            if (!$nodes->current()->hasChildren()) {
                return $nodes->current();
            }

            try {
                return $this($nodes->current()->children());
            } catch (ContentTooDispersed $e) {
                return $nodes->current();
            }
        }

        $dispersion = $nodes->reduce(
            [],
            function(array $dispersion, int $position, Node $node): array {
                $text = (new Text)($node);
                $text = new Str($text);
                $dispersion[$position] = $text->wordCount();

                return $dispersion;
            }
        );
        $quantile = new Quantile(Dataset::fromArray($dispersion));
        $lookup = [];

        //select qartiles that have more words than the average nodes
        for ($i = 1; $i < 5; $i++) {
            $diff = $quantile
                ->quartile($i)
                ->value()
                ->subtract($quantile->quartile($i - 1)->value())
                ->value();

            if ($diff >= $quantile->mean()->value()) {
                $lookup[] = $i;
            }
        }

        if (empty($lookup)) {
            throw new ContentTooDispersed;
        }

        //select the minimum amount of words that needs to be in nodes
        $min = $quantile->quartile(min($lookup))->value()->value();

        $nodes = $nodes->filter(function(int $position) use ($min, $dispersion): bool {
            return $dispersion[$position] >= $min;
        });

        return $this($nodes);
    }
}
