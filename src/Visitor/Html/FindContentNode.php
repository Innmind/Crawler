<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Exception\{
    ContentTooDispersed,
    InvalidArgumentException
};
use Innmind\Xml\{
    NodeInterface,
    Visitor\Text
};
use Innmind\Math\{
    Quantile\Quantile,
    Regression\Dataset
};
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class FindContentNode
{
    /**
     * @param MapInterface<int, NodeInterface> $nodes
     */
    public function __invoke(MapInterface $nodes): NodeInterface
    {
        if (
            (string) $nodes->keyType() !== 'int' ||
            (string) $nodes->valueType() !== NodeInterface::class
        ) {
            throw new InvalidArgumentException;
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
            function(array $dispersion, int $position, NodeInterface $node): array {
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
