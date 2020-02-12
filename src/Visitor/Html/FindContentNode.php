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
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\assertSequence;

final class FindContentNode
{
    /**
     * @param Sequence<Node> $nodes
     */
    public function __invoke(Sequence $nodes): Node
    {
        assertSequence(Node::class, $nodes, 1);

        if ($nodes->size() === 1) {
            if ($nodes->first()->children()->empty()) {
                return $nodes->first();
            }

            try {
                return $this($nodes->first()->children());
            } catch (ContentTooDispersed $e) {
                return $nodes->first();
            }
        }

        $nodes = $nodes->reduce(
            Map::of('int', Node::class),
            static function(Map $children, Node $child): Map {
                return ($children)($children->size(), $child);
            },
        );

        $dispersion = $nodes->reduce(
            [],
            static function(array $dispersion, int $position, Node $node): array {
                $text = (new Text)($node);
                $text = Str::of($text);
                $dispersion[$position] = $text->wordCount();

                return $dispersion;
            }
        );
        $quantile = new Quantile(Dataset::of($dispersion));
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

        if (\count($lookup) === 0) {
            throw new ContentTooDispersed;
        }

        //select the minimum amount of words that needs to be in nodes
        $min = $quantile->quartile(\min($lookup))->value()->value();

        $nodes = $nodes->filter(static function(int $position) use ($min, $dispersion): bool {
            return $dispersion[$position] >= $min;
        });

        return $this($nodes->values());
    }
}
