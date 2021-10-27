<?php

declare(strict_types=1);

namespace Nawarian\Dumm;

use Nawarian\Dumm\WAD\Segment;

abstract class AbstractRenderer implements Renderer
{
    public function __construct(
        public GameState $state,
    ) {}

    protected function update(): void
    {
        // Traverse BSP nodes and trigger self::addSegment() for each segment found
        // Last node is the root node
        $this->traverseBSPNodes(count($this->state->map->nodes()) - 1);
    }

    /**
     * This method is called every time a segment is found by self::update()
     *
     * @param Segment $segment
     */
    protected abstract function handleSegmentFound(Segment $segment): void;

    private function traverseBSPNodes(int $nodeId): void
    {
        $nodes = $this->state->map->nodes();

        // If that's a subsector, render the subsector instead
        if ($nodeId & 0x8000) {
            // "Convert" this int into int(16)
            $nodeId &= 0xFFFF;

            // Get the subSectorId bit only
            $nodeId &= ~0x8000;

            $subSector = $this->state->map->subSectors()[$nodeId];
            foreach (xrange(0, $subSector->segCount) as $i) {
                $segment = $this->state->map->segments()[$subSector->segId + $i];

                $this->handleSegmentFound($segment);
            }
            return;
        }

        $node = $nodes[$nodeId];
        $dx = $this->state->player->position->x - $node->partition->x;
        $dy = $this->state->player->position->y - $node->partition->y;

        $isOnLeftSide = (
                ($dx * $node->partitionChange->y) - ($dy * $node->partitionChange->x)
            ) <= 0;

        if (true === $isOnLeftSide) {
            $this->traverseBSPNodes($nodes[$nodeId]->leftChildId);
            $this->traverseBSPNodes($nodes[$nodeId]->rightChildId);
        } else {
            $this->traverseBSPNodes($nodes[$nodeId]->rightChildId);
            $this->traverseBSPNodes($nodes[$nodeId]->leftChildId);
        }
    }
}
