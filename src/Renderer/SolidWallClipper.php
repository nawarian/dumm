<?php

declare(strict_types=1);

namespace Nawarian\Dumm\Renderer;

use Nawarian\Dumm\{Game, WAD\Segment};
use OutOfRangeException;
use SplDoublyLinkedList;
use SplQueue;
use function Nawarian\Dumm\xrange;

final class SolidWallClipper
{
    /** @var SplQueue<SolidSegmentData> */
    public SplQueue $visibleWalls;

    /** @var SplDoublyLinkedList<SolidSegmentRange>  */
    private SplDoublyLinkedList $solidSegmentRanges;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->solidSegmentRanges = new SplDoublyLinkedList();
        $this->solidSegmentRanges->push(new SolidSegmentRange(PHP_INT_MIN, -1));
        $this->solidSegmentRanges->push(new SolidSegmentRange((int) Game::SCREEN_WIDTH, PHP_INT_MAX));

        $this->visibleWalls = new SplQueue();
    }

    public function registerVisibleWallPortion(Segment $segment, int $v1XScreen, int $v2XScreen): void
    {
        $currentWall = new SolidSegmentRange($v1XScreen, $v2XScreen);
        $i = 0;

        while (
            $this->solidSegmentRanges[$i] !== $this->solidSegmentRanges[$this->solidSegmentRanges->count() - 1]
            && $this->solidSegmentRanges[$i]->xEnd < $currentWall->xStart - 1
        ) {
            ++$i;
        }

        if ($currentWall->xStart < $this->solidSegmentRanges[$i]->xStart) {
            if ($currentWall->xEnd < $this->solidSegmentRanges[$i]->xStart - 1) {
                // Wall is entirely visible, insert it
                $this->solidSegmentRanges->add($i, $currentWall);
                $this->storeWallRange($segment, $currentWall->xStart, $currentWall->xEnd);

                return;
            }
            $this->storeWallRange($segment, $currentWall->xStart, $this->solidSegmentRanges[$i]->xStart - 1);

            // The end is already included, just update start
            $this->solidSegmentRanges[$i]->xStart = $currentWall->xStart;
        }

        // This part is already occupied
        if ($currentWall->xEnd <= $this->solidSegmentRanges[$i]->xEnd) {
            return;
        }

        $j = $i;
        $nextWall = null;
        try {
            $nextWall = $this->solidSegmentRanges[$j + 1];
        } catch (OutOfRangeException $e) {}
        while ($nextWall && $currentWall->xEnd >= ($nextWall->xStart - 1)) {
            // partially clipped by other walls, store each fragment
            $this->storeWallRange(
                $segment,
                $this->solidSegmentRanges[$j]->xEnd + 1,
                $this->solidSegmentRanges[$j + 1]->xStart - 1
            );
            ++$j;

            if ($currentWall->xEnd <= $this->solidSegmentRanges[$j]->xEnd) {
                $this->solidSegmentRanges[$i]->xEnd = $this->solidSegmentRanges[$j]->xEnd;

                if ($this->solidSegmentRanges[$i] !== $this->solidSegmentRanges[$j]) {
                    // Delete a range of walls
                    foreach (xrange(++$i, ++$j) as $offset) {
                        $this->solidSegmentRanges->offsetExists($offset)
                            && $this->solidSegmentRanges->offsetUnset($offset);
                    }
                }
                return;
            }

            try {
                $nextWall = $this->solidSegmentRanges[$j + 1];
            } catch (OutOfRangeException $e) {
                break;
            }
        }

        $this->storeWallRange($segment, $this->solidSegmentRanges[$j]->xEnd + 1, $currentWall->xEnd);
        $this->solidSegmentRanges[$i]->xEnd = $currentWall->xEnd;

        if ($this->solidSegmentRanges[$i] !== $this->solidSegmentRanges[$j]) {
            foreach (xrange(++$i, ++$j) as $offset) {
                $this->solidSegmentRanges->offsetExists($offset)
                    && $this->solidSegmentRanges->offsetUnset($offset);
            }
        }
    }

    private function storeWallRange(Segment $segment, int $v1XScreen, int $v2XScreen): void
    {
        $this->visibleWalls->push(new SolidSegmentData($segment, $v1XScreen, $v2XScreen));
    }
}
